package main

import (
	"encoding/json"
	"errors"
	"fmt"
	"os"
	"sort"
	"time"
)

// jsonMode (--json): los comandos de consulta escriben JSON en la salida estándar.
// Lo usa la app de escritorio, que ejecuta la CLI por debajo.
var jsonMode bool

func printJSON(v any) error {
	enc := json.NewEncoder(os.Stdout)
	enc.SetEscapeHTML(false)
	return enc.Encode(v)
}

// ── repos ───────────────────────────────────────────────────────────────────

type repoInfo struct {
	ID          int    `json:"id"`
	Name        string `json:"name"`
	Project     string `json:"project"`
	Description string `json:"description"`
	Visibility  string `json:"visibility"`
	URL         string `json:"url"`
}

// repos lista los repositorios de los proyectos del usuario (para elegir cuál clonar).
func cmdRepos(args []string) error {
	cfg := loadConfig()
	server := cfg.DefaultServer
	if len(args) > 0 {
		server, _ = normalizeServer(args[0])
	}
	if server == "" || cfg.Tokens[server] == "" {
		return errors.New("no has iniciado sesión. Ejecuta: codeflow login <servidor>")
	}
	api := apiFor(server)
	projects, err := api.Projects()
	if err != nil {
		return err
	}
	var list []repoInfo
	for _, p := range projects {
		repos, err := api.ProjectRepositories(p.ID)
		if err != nil && !isStatus(err, 404) {
			return err
		}
		for _, r := range repos {
			list = append(list, repoInfo{r.ID, r.Name, p.Name, r.Description, r.Visibility, fmt.Sprintf("%s/repositories/%d", server, r.ID)})
		}
	}
	sort.Slice(list, func(i, j int) bool {
		if list[i].Project != list[j].Project {
			return list[i].Project < list[j].Project
		}
		return list[i].Name < list[j].Name
	})
	if jsonMode {
		if list == nil {
			list = []repoInfo{}
		}
		return printJSON(list)
	}
	if len(list) == 0 {
		fmt.Println("No tienes repositorios. Crea un proyecto en la web o pide que te añadan a uno.")
	}
	for _, r := range list {
		fmt.Printf("%-40s %s\n", r.Project+"/"+r.Name, r.URL)
	}
	return nil
}

// ── status --json ───────────────────────────────────────────────────────────

type statusJSON struct {
	Server  string        `json:"server"`
	RepoID  int           `json:"repository_id"`
	Repo    string        `json:"repository"`
	Project string        `json:"project"`
	Branch  string        `json:"branch"`
	Root    string        `json:"root"`
	URL     string        `json:"url"`
	Changes []changeJSON  `json:"changes"`
	Pending []pendingJSON `json:"pending"`
	BaseID  *int          `json:"base_commit_id"`
	Checked string        `json:"checked_at"`
}

type changeJSON struct {
	Path string `json:"path"`
	Kind string `json:"kind"`
}

type pendingJSON struct {
	Message string       `json:"message"`
	Created string       `json:"created_at"`
	Changes []changeJSON `json:"changes"`
}

func statusAsJSON(st *State, changes []FileChange) error {
	out := statusJSON{
		Server: st.Server, RepoID: st.RepoID, Repo: st.RepoName, Project: st.ProjectName, Branch: st.Branch,
		Root: st.root, URL: fmt.Sprintf("%s/repositories/%d", st.Server, st.RepoID), BaseID: st.BaseCommitID,
		Changes: []changeJSON{}, Pending: []pendingJSON{}, Checked: time.Now().Format(time.RFC3339),
	}
	for _, c := range changes {
		out.Changes = append(out.Changes, changeJSON{c.Path, c.Kind})
	}
	for _, p := range st.Pending {
		pj := pendingJSON{Message: p.Message, Created: p.Created, Changes: []changeJSON{}}
		for _, ch := range p.Changes {
			kind := "modificado"
			if ch.Delete {
				kind = "borrado"
			} else if _, ok := st.Remote[ch.Path]; !ok {
				kind = "nuevo"
			}
			pj.Changes = append(pj.Changes, changeJSON{ch.Path, kind})
		}
		out.Pending = append(out.Pending, pj)
	}
	return printJSON(out)
}
