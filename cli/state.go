package main

import (
	"bufio"
	"crypto/sha256"
	"encoding/hex"
	"encoding/json"
	"errors"
	"fmt"
	"io/fs"
	"os"
	"path"
	"path/filepath"
	"sort"
	"strings"
)

const metaDir = ".codeflow"

// FileEntry: un archivo del commit remoto de referencia.
type FileEntry struct {
	BlobID int    `json:"blob_id"`
	Sha    string `json:"sha256"`
	Size   int64  `json:"size"`
}

// PendingChange: cambio de un commit local aún sin subir. El contenido se guarda en .codeflow/objects/<sha>.
type PendingChange struct {
	Path   string `json:"path"`
	Delete bool   `json:"delete,omitempty"`
	Sha    string `json:"sha256,omitempty"`
}

type PendingCommit struct {
	Message string          `json:"message"`
	Changes []PendingChange `json:"changes"`
	Created string          `json:"created_at"`
}

// State es el equivalente a .git: qué repositorio y rama es esta carpeta, sobre qué commit
// del servidor se trabaja (BaseCommitID) y los commits locales pendientes de subir.
type State struct {
	Version      int                  `json:"version"`
	Server       string               `json:"server"`
	RepoID       int                  `json:"repository_id"`
	RepoName     string               `json:"repository"`
	ProjectName  string               `json:"project"`
	BranchID     int                  `json:"branch_id"`
	Branch       string               `json:"branch"`
	BaseCommitID *int                 `json:"base_commit_id"`
	Remote       map[string]FileEntry `json:"remote"`
	Pending      []PendingCommit      `json:"pending"`

	root string
}

// ── Carga y guardado ────────────────────────────────────────────────────────

// findRoot busca hacia arriba la carpeta que contiene .codeflow (como hace git con .git).
func findRoot(start string) (string, error) {
	dir, _ := filepath.Abs(start)
	for {
		if info, err := os.Stat(filepath.Join(dir, metaDir, "state.json")); err == nil && !info.IsDir() {
			return dir, nil
		}
		parent := filepath.Dir(dir)
		if parent == dir {
			return "", errors.New("esta carpeta no es un repositorio de CodeFlow (usa: codeflow clone <url>)")
		}
		dir = parent
	}
}

func loadState() (*State, error) {
	root, err := findRoot(".")
	if err != nil {
		return nil, err
	}
	data, err := os.ReadFile(filepath.Join(root, metaDir, "state.json"))
	if err != nil {
		return nil, err
	}
	st := &State{}
	if err := json.Unmarshal(data, st); err != nil {
		return nil, errors.New(".codeflow/state.json está dañado")
	}
	if st.Remote == nil {
		st.Remote = map[string]FileEntry{}
	}
	st.root = root
	return st, nil
}

func (s *State) save() error {
	data, _ := json.MarshalIndent(s, "", "  ")
	tmp := filepath.Join(s.root, metaDir, "state.json.tmp")
	if err := os.WriteFile(tmp, data, 0o644); err != nil {
		return err
	}
	return os.Rename(tmp, filepath.Join(s.root, metaDir, "state.json"))
}

func (s *State) abs(rel string) string { return filepath.Join(s.root, filepath.FromSlash(rel)) }

// ── Objetos (contenido de los commits locales) ──────────────────────────────

func (s *State) objectPath(sha string) string { return filepath.Join(s.root, metaDir, "objects", sha) }

func (s *State) storeObject(content []byte) (string, error) {
	sha := hashBytes(content)
	p := s.objectPath(sha)
	if _, err := os.Stat(p); err == nil {
		return sha, nil
	}
	if err := os.MkdirAll(filepath.Dir(p), 0o755); err != nil {
		return "", err
	}
	return sha, os.WriteFile(p, content, 0o644)
}

func (s *State) readObject(sha string) ([]byte, error) { return os.ReadFile(s.objectPath(sha)) }

// gcObjects borra los objetos que ya no usa ningún commit pendiente.
func (s *State) gcObjects() {
	used := map[string]bool{}
	for _, c := range s.Pending {
		for _, ch := range c.Changes {
			used[ch.Sha] = true
		}
	}
	entries, _ := os.ReadDir(filepath.Join(s.root, metaDir, "objects"))
	for _, e := range entries {
		if !used[e.Name()] {
			_ = os.Remove(filepath.Join(s.root, metaDir, "objects", e.Name()))
		}
	}
}

// ── Vistas del árbol ────────────────────────────────────────────────────────

// committedView: archivos tal y como quedarían tras aplicar los commits locales pendientes (ruta → sha).
func (s *State) committedView() map[string]string {
	view := make(map[string]string, len(s.Remote))
	for p, e := range s.Remote {
		view[p] = e.Sha
	}
	for _, c := range s.Pending {
		for _, ch := range c.Changes {
			if ch.Delete {
				delete(view, ch.Path)
			} else {
				view[ch.Path] = ch.Sha
			}
		}
	}
	return view
}

// pendingPaths: rutas tocadas por commits locales sin subir.
func (s *State) pendingPaths() map[string]bool {
	paths := map[string]bool{}
	for _, c := range s.Pending {
		for _, ch := range c.Changes {
			paths[ch.Path] = true
		}
	}
	return paths
}

// ── Lectura del disco ───────────────────────────────────────────────────────

type indexEntry struct {
	Size  int64  `json:"size"`
	MTime int64  `json:"mtime"`
	Sha   string `json:"sha256"`
}

// scanDisk devuelve ruta → sha256 de los archivos de la carpeta (sin los ignorados).
// Usa una caché por tamaño y fecha para no volver a leer los archivos que no han cambiado.
func (s *State) scanDisk() (map[string]string, error) {
	ignore := loadIgnore(s.root)
	indexPath := filepath.Join(s.root, metaDir, "index.json")
	index := map[string]indexEntry{}
	if data, err := os.ReadFile(indexPath); err == nil {
		_ = json.Unmarshal(data, &index)
	}

	result := map[string]string{}
	newIndex := map[string]indexEntry{}
	err := filepath.WalkDir(s.root, func(p string, d fs.DirEntry, err error) error {
		if err != nil {
			return nil // archivos sin permiso: se ignoran
		}
		rel, _ := filepath.Rel(s.root, p)
		rel = filepath.ToSlash(rel)
		if rel == "." {
			return nil
		}
		if d.IsDir() {
			if ignore.match(rel, true) {
				return filepath.SkipDir
			}
			return nil
		}
		if !d.Type().IsRegular() || ignore.match(rel, false) {
			return nil
		}
		info, err := d.Info()
		if err != nil {
			return nil
		}
		if cached, ok := index[rel]; ok && cached.Size == info.Size() && cached.MTime == info.ModTime().UnixNano() {
			result[rel] = cached.Sha
			newIndex[rel] = cached
			return nil
		}
		sha, err := hashFile(p)
		if err != nil {
			return err
		}
		result[rel] = sha
		newIndex[rel] = indexEntry{Size: info.Size(), MTime: info.ModTime().UnixNano(), Sha: sha}
		return nil
	})
	if data, e := json.Marshal(newIndex); e == nil {
		_ = os.WriteFile(indexPath, data, 0o644)
	}
	return result, err
}

// Cambios de una vista a otra (ruta → sha; "" = borrado).
type FileChange struct {
	Path string
	Kind string // nuevo | modificado | borrado
	Sha  string
}

func diffViews(from, to map[string]string) []FileChange {
	var out []FileChange
	for p, sha := range to {
		old, ok := from[p]
		switch {
		case !ok:
			out = append(out, FileChange{p, "nuevo", sha})
		case old != sha:
			out = append(out, FileChange{p, "modificado", sha})
		}
	}
	for p := range from {
		if _, ok := to[p]; !ok {
			out = append(out, FileChange{p, "borrado", ""})
		}
	}
	sort.Slice(out, func(i, j int) bool { return out[i].Path < out[j].Path })
	return out
}

// ── Escritura en disco ──────────────────────────────────────────────────────

// safeRel comprueba que una ruta que llega del servidor no se sale de la carpeta del repositorio
// (../, rutas absolutas, unidades de Windows) ni pisa la carpeta interna .codeflow.
func safeRel(rel string) error {
	clean := path.Clean(strings.ReplaceAll(rel, "\\", "/"))
	first := strings.SplitN(clean, "/", 2)[0]
	if rel == "" || clean == "." || strings.HasPrefix(clean, "/") || clean == ".." || strings.HasPrefix(clean, "../") ||
		strings.Contains(first, ":") || filepath.IsAbs(filepath.FromSlash(clean)) || strings.EqualFold(first, metaDir) ||
		strings.ContainsAny(rel, "\x00") {
		return fmt.Errorf("ruta no permitida en el repositorio: %q", rel)
	}
	return nil
}

func (s *State) writeFile(rel string, content []byte) error {
	if err := safeRel(rel); err != nil {
		return err
	}
	p := s.abs(rel)
	if err := os.MkdirAll(filepath.Dir(p), 0o755); err != nil {
		return err
	}
	return os.WriteFile(p, content, 0o644)
}

func (s *State) removeFile(rel string) error {
	if err := safeRel(rel); err != nil {
		return err
	}
	p := s.abs(rel)
	if err := os.Remove(p); err != nil && !errors.Is(err, fs.ErrNotExist) {
		return err
	}
	// Borra las carpetas que se hayan quedado vacías
	for dir := filepath.Dir(p); dir != s.root && strings.HasPrefix(dir, s.root); dir = filepath.Dir(dir) {
		if os.Remove(dir) != nil {
			break
		}
	}
	return nil
}

// ── Utilidades ──────────────────────────────────────────────────────────────

func hashBytes(b []byte) string {
	h := sha256.Sum256(b)
	return hex.EncodeToString(h[:])
}

func hashFile(p string) (string, error) {
	f, err := os.Open(p)
	if err != nil {
		return "", err
	}
	defer f.Close()
	h := sha256.New()
	if _, err := bufio.NewReader(f).WriteTo(h); err != nil {
		return "", err
	}
	return hex.EncodeToString(h.Sum(nil)), nil
}

// ── .codeflowignore ─────────────────────────────────────────────────────────

var defaultIgnore = []string{
	metaDir + "/", ".git/", "node_modules/", "vendor/", ".idea/", ".vscode/", ".gradle/",
	"__pycache__/", ".DS_Store", "Thumbs.db", "desktop.ini", "*.swp", "*~",
}

type ignoreRules struct{ patterns []string }

func loadIgnore(root string) ignoreRules {
	rules := ignoreRules{patterns: append([]string{}, defaultIgnore...)}
	data, err := os.ReadFile(filepath.Join(root, ".codeflowignore"))
	if err != nil {
		return rules
	}
	for _, line := range strings.Split(string(data), "\n") {
		line = strings.TrimSpace(line)
		if line != "" && !strings.HasPrefix(line, "#") {
			rules.patterns = append(rules.patterns, line)
		}
	}
	return rules
}

// match: "carpeta/" ignora esa carpeta en cualquier nivel; "a/b.txt" o "src/*.log" se comparan con
// la ruta completa; "*.log" o "secreto.txt" con el nombre en cualquier nivel.
func (r ignoreRules) match(rel string, isDir bool) bool {
	name := path.Base(rel)
	for _, pat := range r.patterns {
		pat = strings.TrimPrefix(pat, "/")
		if strings.HasSuffix(pat, "/") {
			dirPat := strings.TrimSuffix(pat, "/")
			if isDir && (matchGlob(dirPat, name) || matchGlob(dirPat, rel)) {
				return true
			}
			continue
		}
		if strings.Contains(pat, "/") {
			if matchGlob(pat, rel) {
				return true
			}
			continue
		}
		if matchGlob(pat, name) {
			return true
		}
	}
	return false
}

func matchGlob(pattern, s string) bool {
	ok, err := path.Match(pattern, s)
	return err == nil && ok
}
