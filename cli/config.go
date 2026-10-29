package main

import (
	"encoding/json"
	"errors"
	"fmt"
	"os"
	"path/filepath"
	"strings"
)

// Configuración global (por usuario): servidor por defecto y token de cada servidor.
// Se guarda en ~/.codeflow/config.json (en Windows: C:\Users\<usuario>\.codeflow\config.json).
type Config struct {
	DefaultServer string            `json:"default_server"`
	Tokens        map[string]string `json:"tokens"`
	Users         map[string]string `json:"users"`
}

func configPath() string {
	home, err := os.UserHomeDir()
	if err != nil {
		home = "."
	}
	return filepath.Join(home, ".codeflow", "config.json")
}

func loadConfig() *Config {
	cfg := &Config{Tokens: map[string]string{}, Users: map[string]string{}}
	data, err := os.ReadFile(configPath())
	if err == nil {
		_ = json.Unmarshal(data, cfg)
	}
	if cfg.Tokens == nil {
		cfg.Tokens = map[string]string{}
	}
	if cfg.Users == nil {
		cfg.Users = map[string]string{}
	}
	return cfg
}

func (c *Config) save() error {
	path := configPath()
	if err := os.MkdirAll(filepath.Dir(path), 0o700); err != nil {
		return err
	}
	data, _ := json.MarshalIndent(c, "", "  ")
	return os.WriteFile(path, data, 0o600)
}

// normalizeServer convierte "codeflow.example.com" o "https://x/algo" en "https://x".
func normalizeServer(raw string) (string, error) {
	raw = strings.TrimSpace(raw)
	if raw == "" {
		return "", errors.New("falta la dirección del servidor")
	}
	if !strings.Contains(raw, "://") {
		raw = "https://" + raw
	}
	scheme, rest, _ := strings.Cut(raw, "://")
	host, _, _ := strings.Cut(rest, "/")
	if host == "" {
		return "", fmt.Errorf("dirección de servidor no válida: %s", raw)
	}
	return strings.ToLower(scheme) + "://" + host, nil
}

// apiFor devuelve un cliente con el token guardado para ese servidor.
func apiFor(server string) *API {
	cfg := loadConfig()
	return NewAPI(server, cfg.Tokens[server])
}
