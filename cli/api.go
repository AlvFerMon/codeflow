package main

import (
	"bytes"
	"encoding/json"
	"errors"
	"fmt"
	"io"
	"net/http"
	"net/url"
	"strings"
	"time"
)

// API es un cliente mínimo de la API REST de CodeFlow (Laravel + Sanctum).
type API struct {
	Server string // p. ej. https://codeflow-backend-wheat.vercel.app
	Token  string
	http   *http.Client
}

func NewAPI(server, token string) *API {
	return &API{Server: strings.TrimRight(server, "/"), Token: token, http: &http.Client{Timeout: 120 * time.Second}}
}

// APIError conserva el código HTTP para poder reaccionar (409 = haz pull, 401 = inicia sesión).
type APIError struct {
	Status  int
	Message string
}

func (e *APIError) Error() string { return e.Message }

func (a *API) do(method, path string, query url.Values, body any, out any) error {
	u := a.Server + "/api/" + strings.TrimLeft(path, "/")
	if len(query) > 0 {
		u += "?" + query.Encode()
	}

	var reader io.Reader
	if body != nil {
		data, err := json.Marshal(body)
		if err != nil {
			return err
		}
		reader = bytes.NewReader(data)
	}

	req, err := http.NewRequest(method, u, reader)
	if err != nil {
		return err
	}
	req.Header.Set("Accept", "application/json")
	req.Header.Set("User-Agent", "codeflow-cli/"+Version)
	if body != nil {
		req.Header.Set("Content-Type", "application/json")
	}
	if a.Token != "" {
		req.Header.Set("Authorization", "Bearer "+a.Token)
	}

	resp, err := a.http.Do(req)
	if err != nil {
		return fmt.Errorf("no se pudo conectar con %s: %w", a.Server, err)
	}
	defer resp.Body.Close()
	raw, _ := io.ReadAll(resp.Body)

	if resp.StatusCode >= 400 {
		return &APIError{Status: resp.StatusCode, Message: errorMessage(resp.StatusCode, raw)}
	}
	if out != nil && len(raw) > 0 {
		if err := json.Unmarshal(raw, out); err != nil {
			return fmt.Errorf("respuesta inesperada del servidor (%d): %w", resp.StatusCode, err)
		}
	}
	return nil
}

func errorMessage(status int, raw []byte) string {
	var body struct {
		Message string              `json:"message"`
		Errors  map[string][]string `json:"errors"`
	}
	_ = json.Unmarshal(raw, &body)
	for _, msgs := range body.Errors {
		if len(msgs) > 0 {
			return msgs[0]
		}
	}
	switch {
	case status == 401:
		return "No has iniciado sesión o la sesión ha caducado. Ejecuta: codeflow login"
	case status == 404 && (body.Message == "" || strings.Contains(strings.ToLower(body.Message), "not found")):
		return "No encontrado (o no tienes acceso)."
	case status == 413:
		return "El envío es demasiado grande para el servidor."
	case body.Message != "":
		return body.Message
	}
	return fmt.Sprintf("Error del servidor (%d).", status)
}

func isStatus(err error, status int) bool {
	var apiErr *APIError
	return errors.As(err, &apiErr) && apiErr.Status == status
}

// ── Tipos de la API ─────────────────────────────────────────────────────────

type User struct {
	ID    int    `json:"id"`
	Name  string `json:"name"`
	Email string `json:"email"`
}

type Repository struct {
	ID            int    `json:"id"`
	Name          string `json:"name"`
	Description   string `json:"description"`
	Visibility    string `json:"visibility"`
	DefaultBranch string `json:"default_branch"`
	Project       *struct {
		ID   int    `json:"id"`
		Name string `json:"name"`
	} `json:"project"`
}

type Branch struct {
	ID           int    `json:"id"`
	Name         string `json:"name"`
	HeadCommitID *int   `json:"head_commit_id"`
	IsDefault    bool   `json:"is_default"`
}

type ManifestFile struct {
	Path   string `json:"path"`
	BlobID int    `json:"blob_id"`
	Size   int64  `json:"size"`
	Binary bool   `json:"binary"`
}

type Manifest struct {
	Branch       string         `json:"branch"`
	HeadCommitID *int           `json:"head_commit_id"`
	CommitID     *int           `json:"commit_id"`
	Files        []ManifestFile `json:"files"`
}

type FileContent struct {
	Path     string `json:"path"`
	BlobID   int    `json:"blob_id"`
	Encoding string `json:"encoding"`
	Content  string `json:"content"`
}

type Commit struct {
	ID        int    `json:"id"`
	Sha       string `json:"sha"`
	Message   string `json:"message"`
	CreatedAt string `json:"created_at"`
	Author    *struct {
		Name string `json:"name"`
	} `json:"author"`
}

type Change struct {
	Path     string `json:"path"`
	Content  string `json:"content,omitempty"`
	Encoding string `json:"encoding,omitempty"`
	Delete   bool   `json:"delete,omitempty"`
}

// ── Llamadas ────────────────────────────────────────────────────────────────

func (a *API) Login(email, password, device string) (string, User, error) {
	var out struct {
		Token string `json:"token"`
		User  User   `json:"user"`
	}
	err := a.do("POST", "auth/login", nil, map[string]string{"email": email, "password": password, "device_name": device}, &out)
	return out.Token, out.User, err
}

func (a *API) Logout() error { return a.do("POST", "auth/logout", nil, nil, nil) }

func (a *API) Me() (User, error) {
	var u User
	return u, a.do("GET", "me", nil, nil, &u)
}

func (a *API) Repository(id int) (Repository, error) {
	var r Repository
	return r, a.do("GET", fmt.Sprintf("repositories/%d", id), nil, nil, &r)
}

type Project struct {
	ID   int    `json:"id"`
	Name string `json:"name"`
}

// Projects: proyectos de los que el usuario es miembro (todas las páginas).
func (a *API) Projects() ([]Project, error) {
	var all []Project
	for p := 1; ; p++ {
		var page struct {
			Data     []Project `json:"data"`
			LastPage int       `json:"last_page"`
		}
		if err := a.do("GET", "projects", url.Values{"page": {fmt.Sprint(p)}}, nil, &page); err != nil {
			return nil, err
		}
		all = append(all, page.Data...)
		if p >= page.LastPage {
			return all, nil
		}
	}
}

func (a *API) ProjectRepositories(projectID int) ([]Repository, error) {
	var all []Repository
	for p := 1; ; p++ {
		var page struct {
			Data     []Repository `json:"data"`
			LastPage int          `json:"last_page"`
		}
		if err := a.do("GET", fmt.Sprintf("projects/%d/repositories", projectID), url.Values{"page": {fmt.Sprint(p)}}, nil, &page); err != nil {
			return nil, err
		}
		all = append(all, page.Data...)
		if p >= page.LastPage {
			return all, nil
		}
	}
}

func (a *API) Branches(repoID int) ([]Branch, error) {
	var page struct {
		Data     []Branch `json:"data"`
		LastPage int      `json:"last_page"`
	}
	var all []Branch
	for p := 1; ; p++ {
		if err := a.do("GET", fmt.Sprintf("repositories/%d/branches", repoID), url.Values{"page": {fmt.Sprint(p)}}, nil, &page); err != nil {
			return nil, err
		}
		all = append(all, page.Data...)
		if p >= page.LastPage {
			return all, nil
		}
	}
}

func (a *API) CreateBranch(repoID int, name string, fromBranchID int) (Branch, error) {
	var b Branch
	return b, a.do("POST", fmt.Sprintf("repositories/%d/branches", repoID), nil,
		map[string]any{"name": name, "from_branch_id": fromBranchID}, &b)
}

func (a *API) Manifest(branchID int, commitID *int) (Manifest, error) {
	var m Manifest
	q := url.Values{}
	if commitID != nil {
		q.Set("commit_id", fmt.Sprint(*commitID))
	}
	return m, a.do("GET", fmt.Sprintf("branches/%d/manifest", branchID), q, nil, &m)
}

// Files descarga el contenido de varios archivos de un commit, en lotes.
func (a *API) Files(branchID int, commitID *int, paths []string, progress func(done int)) ([]FileContent, error) {
	var result []FileContent
	pending := paths
	for len(pending) > 0 {
		chunk := pending
		if len(chunk) > 200 {
			chunk = chunk[:200]
		}
		var out struct {
			Files     []FileContent `json:"files"`
			Remaining []string      `json:"remaining"`
		}
		body := map[string]any{"paths": chunk}
		if commitID != nil {
			body["commit_id"] = *commitID
		}
		if err := a.do("POST", fmt.Sprintf("branches/%d/files/batch", branchID), nil, body, &out); err != nil {
			return nil, err
		}
		if len(out.Files) == 0 {
			return nil, fmt.Errorf("el servidor no devolvió %s", chunk[0])
		}
		result = append(result, out.Files...)
		pending = append(out.Remaining, pending[len(chunk):]...)
		if progress != nil {
			progress(len(result))
		}
	}
	return result, nil
}

func (a *API) Commit(branchID int, message string, baseCommitID *int, changes []Change) (Commit, error) {
	var c Commit
	body := map[string]any{"message": message, "changes": changes, "base_commit_id": baseCommitID}
	return c, a.do("POST", fmt.Sprintf("branches/%d/commits", branchID), nil, body, &c)
}

func (a *API) History(branchID, page int) ([]Commit, error) {
	var out struct {
		Data []Commit `json:"data"`
	}
	return out.Data, a.do("GET", fmt.Sprintf("branches/%d/commits", branchID), url.Values{"page": {fmt.Sprint(page)}}, nil, &out)
}
