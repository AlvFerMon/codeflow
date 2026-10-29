package main

import (
	"bufio"
	"encoding/base64"
	"errors"
	"fmt"
	"os"
	"path/filepath"
	"regexp"
	"sort"
	"strconv"
	"strings"
	"time"
)

// Tamaño máximo de cada envío (Vercel admite ~4,5 MB por petición). Un commit más grande se sube por partes.
const maxPushBytes = 3 * 1024 * 1024

// ── login / logout / whoami ─────────────────────────────────────────────────

func cmdLogin(args []string) error {
	flags, rest := parseFlags(args, "email")
	passwordFromStdin := false
	for i, a := range rest {
		if a == "--password-stdin" {
			passwordFromStdin = true
			rest = append(rest[:i], rest[i+1:]...)
			break
		}
	}
	cfg := loadConfig()
	server := cfg.DefaultServer
	if len(rest) > 0 {
		server = rest[0]
	}
	if server == "" {
		server = prompt("Servidor (p. ej. https://codeflow-backend-wheat.vercel.app): ")
	}
	server, err := normalizeServer(server)
	if err != nil {
		return err
	}

	email := flags["email"]
	if email == "" {
		email = prompt("Correo: ")
	}
	var password string
	if passwordFromStdin {
		// La app de escritorio pasa la contraseña por la entrada estándar (nunca como argumento)
		password = prompt("")
	} else if password, err = readPassword("Contraseña: "); err != nil {
		return err
	}
	host, _ := os.Hostname()
	token, user, err := NewAPI(server, "").Login(email, password, "codeflow-cli ("+host+")")
	if err != nil {
		return err
	}

	cfg.Tokens[server] = token
	cfg.Users[server] = user.Email
	cfg.DefaultServer = server
	if err := cfg.save(); err != nil {
		return err
	}
	if jsonMode {
		return printJSON(map[string]any{"server": server, "user": user})
	}
	fmt.Printf("Sesión iniciada en %s como %s (%s).\n", server, user.Name, user.Email)
	return nil
}

func cmdLogout(args []string) error {
	cfg := loadConfig()
	server := cfg.DefaultServer
	if len(args) > 0 {
		server, _ = normalizeServer(args[0])
	}
	if cfg.Tokens[server] == "" {
		fmt.Println("No había ninguna sesión iniciada en", server)
		return nil
	}
	_ = NewAPI(server, cfg.Tokens[server]).Logout()
	delete(cfg.Tokens, server)
	delete(cfg.Users, server)
	if err := cfg.save(); err != nil {
		return err
	}
	fmt.Println("Sesión cerrada en", server)
	return nil
}

func cmdWhoami([]string) error {
	server := loadConfig().DefaultServer
	if st, err := loadState(); err == nil {
		server = st.Server
	}
	if server == "" {
		return errors.New("no has iniciado sesión. Ejecuta: codeflow login <servidor>")
	}
	user, err := apiFor(server).Me()
	if err != nil {
		return err
	}
	if jsonMode {
		return printJSON(map[string]any{"server": server, "user": user})
	}
	fmt.Printf("%s (%s) en %s\n", user.Name, user.Email, server)
	return nil
}

// ── clone ───────────────────────────────────────────────────────────────────

var repoURL = regexp.MustCompile(`^(https?://[^/]+)/(?:api/)?repositories/(\d+)`)

func cmdClone(args []string) error {
	flags, rest := parseFlags(args, "b", "branch")
	if len(rest) < 1 {
		return errors.New("uso: codeflow clone <url del repositorio> [carpeta] [-b rama]\n  la url es la de la página del repositorio, p. ej. https://servidor/repositories/12")
	}

	var server string
	var repoID int
	if m := repoURL.FindStringSubmatch(strings.TrimSpace(rest[0])); m != nil {
		server, _ = normalizeServer(m[1])
		repoID, _ = strconv.Atoi(m[2])
	} else if id, err := strconv.Atoi(rest[0]); err == nil && loadConfig().DefaultServer != "" {
		server, repoID = loadConfig().DefaultServer, id
	} else {
		return fmt.Errorf("no entiendo %q: usa la url de la página del repositorio (…/repositories/<número>)", rest[0])
	}

	api := apiFor(server)
	repo, err := api.Repository(repoID)
	if err != nil {
		return err
	}
	branch, err := findBranch(api, repo, flags["branch"]+flags["b"])
	if err != nil {
		return err
	}

	dir := repo.Name
	if len(rest) > 1 {
		dir = rest[1]
	}
	if entries, err := os.ReadDir(dir); err == nil && len(entries) > 0 {
		return fmt.Errorf("la carpeta %s ya existe y no está vacía", dir)
	}
	if err := os.MkdirAll(filepath.Join(dir, metaDir), 0o755); err != nil {
		return err
	}
	root, _ := filepath.Abs(dir)

	st := &State{Version: 1, Server: server, RepoID: repo.ID, RepoName: repo.Name, Remote: map[string]FileEntry{}, root: root}
	if repo.Project != nil {
		st.ProjectName = repo.Project.Name
	}
	fmt.Printf("Clonando %s en %s (rama %s)...\n", fullName(st), dir, branch.Name)
	if err := checkoutBranch(api, st, branch, true); err != nil {
		os.RemoveAll(filepath.Join(dir, metaDir))
		return err
	}
	fmt.Printf("Listo: %d archivos. Entra en la carpeta con: cd %s\n", len(st.Remote), dir)
	return nil
}

func findBranch(api *API, repo Repository, name string) (Branch, error) {
	branches, err := api.Branches(repo.ID)
	if err != nil {
		return Branch{}, err
	}
	if name == "" {
		name = repo.DefaultBranch
	}
	for _, b := range branches {
		if b.Name == name {
			return b, nil
		}
	}
	return Branch{}, fmt.Errorf("la rama %q no existe en %s", name, repo.Name)
}

// checkoutBranch descarga la rama (todo si fresh, o solo las diferencias) y la deja como base.
func checkoutBranch(api *API, st *State, branch Branch, fresh bool) error {
	manifest, err := api.Manifest(branch.ID, nil)
	if err != nil {
		return err
	}
	target := map[string]ManifestFile{}
	for _, f := range manifest.Files {
		target[f.Path] = f
	}

	var toDownload []string
	for p, f := range target {
		if old, ok := st.Remote[p]; fresh || !ok || old.BlobID != f.BlobID {
			toDownload = append(toDownload, p)
		}
	}
	sort.Strings(toDownload)

	newRemote := map[string]FileEntry{}
	for p, f := range target {
		if old, ok := st.Remote[p]; ok && !fresh && old.BlobID == f.BlobID {
			newRemote[p] = old
		}
	}
	if err := download(api, st, branch.ID, manifest.HeadCommitID, toDownload, newRemote); err != nil {
		return err
	}
	if !fresh {
		for p := range st.Remote {
			if _, ok := target[p]; !ok {
				if err := st.removeFile(p); err != nil {
					return err
				}
			}
		}
	}

	st.Remote = newRemote
	st.BranchID = branch.ID
	st.Branch = branch.Name
	st.BaseCommitID = manifest.HeadCommitID
	return st.save()
}

// download baja los archivos indicados de un commit y los escribe en disco.
func download(api *API, st *State, branchID int, commitID *int, paths []string, into map[string]FileEntry) error {
	if len(paths) == 0 {
		return nil
	}
	files, err := api.Files(branchID, commitID, paths, func(done int) {
		if len(paths) > 20 {
			fmt.Printf("\r  descargados %d/%d archivos", done, len(paths))
		}
	})
	if len(paths) > 20 {
		fmt.Println()
	}
	if err != nil {
		return err
	}
	for _, f := range files {
		content, err := decodeContent(f)
		if err != nil {
			return err
		}
		if err := st.writeFile(f.Path, content); err != nil {
			return err
		}
		into[f.Path] = FileEntry{BlobID: f.BlobID, Sha: hashBytes(content), Size: int64(len(content))}
	}
	return nil
}

func decodeContent(f FileContent) ([]byte, error) {
	if f.Encoding == "base64" {
		b, err := base64.StdEncoding.DecodeString(f.Content)
		if err != nil {
			return nil, fmt.Errorf("contenido no válido en %s", f.Path)
		}
		return b, nil
	}
	return []byte(f.Content), nil
}

// ── status ──────────────────────────────────────────────────────────────────

// localChanges: diferencias entre el disco y lo ya confirmado (commits locales incluidos).
func localChanges(st *State) ([]FileChange, error) {
	disk, err := st.scanDisk()
	if err != nil {
		return nil, err
	}
	view := st.committedView()
	ignore := loadIgnore(st.root)
	for p := range view {
		if ignore.match(p, false) || ignoredByDir(ignore, p) {
			delete(view, p) // los archivos ignorados quedan fuera de los cambios (no se borran del servidor)
		}
	}
	return diffViews(view, disk), nil
}

func ignoredByDir(ignore ignoreRules, p string) bool {
	parts := strings.Split(p, "/")
	for i := 1; i < len(parts); i++ {
		if ignore.match(strings.Join(parts[:i], "/"), true) {
			return true
		}
	}
	return false
}

func cmdStatus([]string) error {
	st, err := loadState()
	if err != nil {
		return err
	}
	changes, err := localChanges(st)
	if err != nil {
		return err
	}
	if jsonMode {
		return statusAsJSON(st, changes)
	}

	fmt.Printf("Repositorio %s · rama %s\n", fullName(st), st.Branch)
	if n := len(st.Pending); n > 0 {
		fmt.Printf("Tienes %d commit(s) sin subir (usa: codeflow push)\n", n)
	}
	if len(changes) == 0 {
		if len(st.Pending) == 0 {
			fmt.Println("No hay cambios. Todo coincide con el servidor (desde tu último pull).")
		} else {
			fmt.Println("No hay cambios sin confirmar.")
		}
		return nil
	}
	fmt.Println("Cambios sin confirmar (usa: codeflow commit -m \"mensaje\"):")
	printChanges(changes)
	return nil
}

func printChanges(changes []FileChange) {
	for _, c := range changes {
		mark := map[string]string{"nuevo": "+", "modificado": "~", "borrado": "-"}[c.Kind]
		fmt.Printf("  %s %-11s %s\n", mark, c.Kind, c.Path)
	}
}

// ── commit ──────────────────────────────────────────────────────────────────

func cmdCommit(args []string) error {
	flags, _ := parseFlags(args, "m", "message")
	message := strings.TrimSpace(flags["m"] + flags["message"])
	if message == "" {
		return errors.New(`falta el mensaje: codeflow commit -m "qué has cambiado"`)
	}
	st, err := loadState()
	if err != nil {
		return err
	}
	changes, err := localChanges(st)
	if err != nil {
		return err
	}
	if len(changes) == 0 {
		return errors.New("no hay cambios que confirmar")
	}

	commit := PendingCommit{Message: message, Created: time.Now().Format(time.RFC3339)}
	for _, c := range changes {
		pc := PendingChange{Path: c.Path, Delete: c.Kind == "borrado"}
		if !pc.Delete {
			content, err := os.ReadFile(st.abs(c.Path))
			if err != nil {
				return err
			}
			if pc.Sha, err = st.storeObject(content); err != nil {
				return err
			}
		}
		commit.Changes = append(commit.Changes, pc)
	}
	st.Pending = append(st.Pending, commit)
	if err := st.save(); err != nil {
		return err
	}
	fmt.Printf("Commit local creado con %d archivo(s). Súbelo con: codeflow push\n", len(commit.Changes))
	printChanges(changes)
	return nil
}

// ── push ────────────────────────────────────────────────────────────────────

func cmdPush([]string) error {
	st, err := loadState()
	if err != nil {
		return err
	}
	if len(st.Pending) == 0 {
		if changes, _ := localChanges(st); len(changes) > 0 {
			return errors.New("no hay commits que subir; primero confirma los cambios: codeflow commit -m \"mensaje\"")
		}
		fmt.Println("No hay nada que subir.")
		return nil
	}
	api := apiFor(st.Server)

	for len(st.Pending) > 0 {
		pc := st.Pending[0]
		parts, err := splitChanges(st, pc)
		if err != nil {
			return err
		}
		for i, part := range parts {
			msg := pc.Message
			if len(parts) > 1 {
				msg = fmt.Sprintf("%s (parte %d de %d)", pc.Message, i+1, len(parts))
			}
			commit, err := api.Commit(st.BranchID, msg, st.BaseCommitID, part)
			if err != nil {
				if isStatus(err, 409) {
					return errors.New("la rama tiene cambios nuevos en el servidor que no tienes.\n  Ejecuta: codeflow pull   y después: codeflow push")
				}
				return err
			}
			st.BaseCommitID = &commit.ID
			fmt.Printf("Subido %s  %s\n", shortSha(commit.Sha), msg)
		}

		// El servidor ya tiene este commit: se actualiza la referencia con los ids de sus archivos
		if err := refreshRemote(api, st, pc); err != nil {
			return err
		}
		st.Pending = st.Pending[1:]
		if err := st.save(); err != nil {
			return err
		}
	}
	st.gcObjects()
	fmt.Println("Push completado.")
	return nil
}

// splitChanges convierte un commit local en uno o varios envíos de menos de maxPushBytes.
func splitChanges(st *State, pc PendingCommit) ([][]Change, error) {
	var parts [][]Change
	var current []Change
	size := 0
	for _, ch := range pc.Changes {
		c := Change{Path: ch.Path, Delete: ch.Delete}
		if !ch.Delete {
			content, err := st.readObject(ch.Sha)
			if err != nil {
				return nil, fmt.Errorf("falta el contenido de %s en .codeflow/objects", ch.Path)
			}
			c.Content = base64.StdEncoding.EncodeToString(content)
			c.Encoding = "base64"
		}
		if len(current) > 0 && size+len(c.Content) > maxPushBytes {
			parts = append(parts, current)
			current, size = nil, 0
		}
		current = append(current, c)
		size += len(c.Content) + len(c.Path) + 64
	}
	if len(current) > 0 {
		parts = append(parts, current)
	}
	return parts, nil
}

func refreshRemote(api *API, st *State, pc PendingCommit) error {
	manifest, err := api.Manifest(st.BranchID, st.BaseCommitID)
	if err != nil {
		return err
	}
	byPath := map[string]ManifestFile{}
	for _, f := range manifest.Files {
		byPath[f.Path] = f
	}
	for _, ch := range pc.Changes {
		if ch.Delete {
			delete(st.Remote, ch.Path)
		} else if f, ok := byPath[ch.Path]; ok {
			st.Remote[ch.Path] = FileEntry{BlobID: f.BlobID, Sha: ch.Sha, Size: f.Size}
		}
	}
	return nil
}

// ── pull ────────────────────────────────────────────────────────────────────

func cmdPull([]string) error {
	st, err := loadState()
	if err != nil {
		return err
	}
	api := apiFor(st.Server)
	manifest, err := api.Manifest(st.BranchID, nil)
	if err != nil {
		return err
	}
	if sameCommit(manifest.HeadCommitID, st.BaseCommitID) {
		fmt.Println("Ya estás al día.")
		return nil
	}

	remote := map[string]ManifestFile{}
	for _, f := range manifest.Files {
		remote[f.Path] = f
	}
	var changed, deleted []string
	for p, f := range remote {
		if old, ok := st.Remote[p]; !ok || old.BlobID != f.BlobID {
			changed = append(changed, p)
		}
	}
	for p := range st.Remote {
		if _, ok := remote[p]; !ok {
			deleted = append(deleted, p)
		}
	}
	sort.Strings(changed)
	sort.Strings(deleted)

	// Lo que has tocado tú: commits sin subir + cambios sin confirmar
	mine := st.pendingPaths()
	local, err := localChanges(st)
	if err != nil {
		return err
	}
	for _, c := range local {
		mine[c.Path] = true
	}
	var conflicts []string
	for _, p := range append(append([]string{}, changed...), deleted...) {
		if mine[p] {
			conflicts = append(conflicts, p)
		}
	}
	if len(conflicts) > 0 && jsonMode {
		_ = printJSON(map[string]any{"conflicts": conflicts})
		return errors.New("conflicto: hay archivos cambiados en el servidor y en tu carpeta")
	}
	if len(conflicts) > 0 {
		fmt.Println("No se ha descargado nada: estos archivos han cambiado en el servidor y también en tu carpeta:")
		for _, p := range conflicts {
			fmt.Println("   ", p)
		}
		return errors.New("guarda una copia de tus versiones, deshaz tus cambios en esos archivos (codeflow restore <archivo>) y vuelve a hacer pull")
	}

	newRemote := map[string]FileEntry{}
	for p, e := range st.Remote {
		if f, ok := remote[p]; ok && f.BlobID == e.BlobID {
			newRemote[p] = e
		}
	}
	if err := download(api, st, st.BranchID, manifest.HeadCommitID, changed, newRemote); err != nil {
		return err
	}
	for _, p := range deleted {
		if err := st.removeFile(p); err != nil {
			return err
		}
	}
	st.Remote = newRemote
	st.BaseCommitID = manifest.HeadCommitID
	if err := st.save(); err != nil {
		return err
	}

	fmt.Printf("Actualizado: %d archivo(s) nuevos o modificados, %d borrado(s).\n", len(changed), len(deleted))
	for _, p := range changed {
		fmt.Println("  ~", p)
	}
	for _, p := range deleted {
		fmt.Println("  -", p)
	}
	if len(st.Pending) > 0 {
		fmt.Printf("Tus %d commit(s) locales siguen pendientes: súbelos con codeflow push\n", len(st.Pending))
	}
	return nil
}

// ── restore ─────────────────────────────────────────────────────────────────

// restore deshace los cambios sin confirmar de un archivo (vuelve a la última versión confirmada).
func cmdRestore(args []string) error {
	if len(args) == 0 {
		return errors.New("uso: codeflow restore <archivo> [más archivos]   (o . para todo)")
	}
	st, err := loadState()
	if err != nil {
		return err
	}
	changes, err := localChanges(st)
	if err != nil {
		return err
	}
	view := st.committedView()
	all := len(args) == 1 && args[0] == "."
	wanted := map[string]bool{}
	for _, a := range args {
		rel, err := filepath.Rel(st.root, mustAbs(a))
		if err == nil {
			wanted[filepath.ToSlash(rel)] = true
		}
	}

	var needRemote []string
	restored := 0
	for _, c := range changes {
		if !all && !wanted[c.Path] {
			continue
		}
		sha, existed := view[c.Path]
		switch {
		case !existed:
			if err := st.removeFile(c.Path); err != nil {
				return err
			}
		case st.isPendingSha(sha):
			content, err := st.readObject(sha)
			if err != nil {
				return err
			}
			if err := st.writeFile(c.Path, content); err != nil {
				return err
			}
		default:
			needRemote = append(needRemote, c.Path)
			continue
		}
		restored++
		fmt.Println("  restaurado", c.Path)
	}
	if len(needRemote) > 0 {
		into := map[string]FileEntry{}
		if err := download(apiFor(st.Server), st, st.BranchID, st.BaseCommitID, needRemote, into); err != nil {
			return err
		}
		for _, p := range needRemote {
			fmt.Println("  restaurado", p)
		}
		restored += len(needRemote)
	}
	if restored == 0 {
		fmt.Println("No había cambios que deshacer en esos archivos.")
	}
	return nil
}

func (s *State) isPendingSha(sha string) bool {
	for _, c := range s.Pending {
		for _, ch := range c.Changes {
			if ch.Sha == sha {
				return true
			}
		}
	}
	return false
}

// ── log ─────────────────────────────────────────────────────────────────────

func cmdLog(args []string) error {
	st, err := loadState()
	if err != nil {
		return err
	}
	commits, err := apiFor(st.Server).History(st.BranchID, 1)
	if err != nil {
		return err
	}
	if jsonMode {
		type entry struct {
			ID      int    `json:"id"`
			Sha     string `json:"sha"`
			Author  string `json:"author"`
			Date    string `json:"date"`
			Message string `json:"message"`
			Current bool   `json:"current"`
		}
		out := map[string]any{"pending": []string{}, "commits": []entry{}}
		var pending []string
		for i := len(st.Pending) - 1; i >= 0; i-- {
			pending = append(pending, st.Pending[i].Message)
		}
		if pending != nil {
			out["pending"] = pending
		}
		list := []entry{}
		for _, c := range commits {
			author := ""
			if c.Author != nil {
				author = c.Author.Name
			}
			list = append(list, entry{c.ID, c.Sha, author, c.CreatedAt, c.Message, st.BaseCommitID != nil && c.ID == *st.BaseCommitID})
		}
		out["commits"] = list
		return printJSON(out)
	}
	for i := len(st.Pending) - 1; i >= 0; i-- {
		fmt.Printf("(sin subir)  %s\n", st.Pending[i].Message)
	}
	for _, c := range commits {
		author := "?"
		if c.Author != nil {
			author = c.Author.Name
		}
		date := c.CreatedAt
		if t, err := time.Parse(time.RFC3339Nano, c.CreatedAt); err == nil {
			date = t.Local().Format("2006-01-02 15:04")
		}
		mark := " "
		if st.BaseCommitID != nil && c.ID == *st.BaseCommitID {
			mark = "*"
		}
		fmt.Printf("%s %s  %s  %-18s %s\n", mark, shortSha(c.Sha), date, truncate(author, 18), firstLine(c.Message))
	}
	return nil
}

// ── branch / checkout ───────────────────────────────────────────────────────

func cmdBranch(args []string) error {
	st, err := loadState()
	if err != nil {
		return err
	}
	api := apiFor(st.Server)
	if len(args) == 0 {
		branches, err := api.Branches(st.RepoID)
		if err != nil {
			return err
		}
		if jsonMode {
			type entry struct {
				Name    string `json:"name"`
				Current bool   `json:"current"`
				Default bool   `json:"default"`
			}
			list := []entry{}
			for _, b := range branches {
				list = append(list, entry{b.Name, b.ID == st.BranchID, b.IsDefault})
			}
			return printJSON(list)
		}
		for _, b := range branches {
			mark := " "
			if b.ID == st.BranchID {
				mark = "*"
			}
			fmt.Printf("%s %s\n", mark, b.Name)
		}
		return nil
	}
	if len(st.Pending) > 0 {
		return errors.New("tienes commits sin subir: haz codeflow push antes de crear una rama")
	}
	b, err := api.CreateBranch(st.RepoID, args[0], st.BranchID)
	if err != nil {
		return err
	}
	fmt.Printf("Rama %s creada a partir de %s. Cámbiate con: codeflow checkout %s\n", b.Name, st.Branch, b.Name)
	return nil
}

func cmdCheckout(args []string) error {
	if len(args) == 0 {
		return errors.New("uso: codeflow checkout <rama>")
	}
	st, err := loadState()
	if err != nil {
		return err
	}
	if len(st.Pending) > 0 {
		return errors.New("tienes commits sin subir: haz codeflow push antes de cambiar de rama")
	}
	if changes, err := localChanges(st); err != nil {
		return err
	} else if len(changes) > 0 {
		printChanges(changes)
		return errors.New("tienes cambios sin confirmar: haz commit y push, o deshazlos con codeflow restore .")
	}
	api := apiFor(st.Server)
	repo, err := api.Repository(st.RepoID)
	if err != nil {
		return err
	}
	branch, err := findBranch(api, repo, args[0])
	if err != nil {
		return err
	}
	if err := checkoutBranch(api, st, branch, false); err != nil {
		return err
	}
	fmt.Printf("Ahora estás en la rama %s.\n", branch.Name)
	return nil
}

// ── utilidades ──────────────────────────────────────────────────────────────

// parseFlags admite -m "x", --message "x" y --message=x; devuelve también los argumentos sueltos.
func parseFlags(args []string, names ...string) (map[string]string, []string) {
	known := map[string]bool{}
	for _, n := range names {
		known[n] = true
	}
	flags := map[string]string{}
	var rest []string
	for i := 0; i < len(args); i++ {
		a := args[i]
		if strings.HasPrefix(a, "-") && len(a) > 1 {
			name, value, hasValue := strings.Cut(strings.TrimLeft(a, "-"), "=")
			if known[name] {
				if !hasValue && i+1 < len(args) {
					i++
					value = args[i]
				}
				flags[name] = value
				continue
			}
		}
		rest = append(rest, a)
	}
	return flags, rest
}

// Un único lector de la entrada estándar: crear uno nuevo en cada pregunta perdería lo ya leído en su búfer.
var stdin = bufio.NewReader(os.Stdin)

func prompt(label string) string {
	fmt.Print(label)
	line, _ := stdin.ReadString('\n')
	return strings.TrimSpace(line)
}

func fullName(st *State) string {
	if st.ProjectName != "" {
		return st.ProjectName + "/" + st.RepoName
	}
	return st.RepoName
}

func shortSha(sha string) string {
	if len(sha) > 7 {
		return sha[:7]
	}
	return sha
}

func firstLine(s string) string {
	line, _, _ := strings.Cut(s, "\n")
	return line
}

func truncate(s string, n int) string {
	if r := []rune(s); len(r) > n {
		return string(r[:n-1]) + "…"
	}
	return s
}

func sameCommit(a, b *int) bool {
	if a == nil || b == nil {
		return a == nil && b == nil
	}
	return *a == *b
}

func mustAbs(p string) string {
	abs, err := filepath.Abs(p)
	if err != nil {
		return p
	}
	return abs
}
