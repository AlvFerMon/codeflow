//go:build !windows

package main

import (
	"fmt"
	"os"
	"path/filepath"
	"strings"
)

func binaryName() string { return "codeflow" }

// ~/.local/bin: carpeta estándar de programas del usuario en Linux y macOS
func installDir() string {
	home, _ := os.UserHomeDir()
	return filepath.Join(home, ".local", "bin")
}

const pathLine = `export PATH="$HOME/.local/bin:$PATH" # añadido por codeflow install`

// Añade ~/.local/bin al PATH en ~/.profile (y ~/.zshrc / ~/.bashrc si existen) si aún no está
func addToPath(dir string) (bool, error) {
	if pathContains(os.Getenv("PATH"), dir) {
		return false, nil
	}
	home, _ := os.UserHomeDir()
	files := []string{filepath.Join(home, ".profile")}
	for _, rc := range []string{".zshrc", ".bashrc"} {
		if _, err := os.Stat(filepath.Join(home, rc)); err == nil {
			files = append(files, filepath.Join(home, rc))
		}
	}
	for _, f := range files {
		data, _ := os.ReadFile(f)
		if strings.Contains(string(data), ".local/bin") {
			continue
		}
		fh, err := os.OpenFile(f, os.O_APPEND|os.O_CREATE|os.O_WRONLY, 0o644)
		if err != nil {
			return false, err
		}
		fmt.Fprintln(fh, "\n"+pathLine)
		fh.Close()
	}
	return true, nil
}

func removeFromPath(string) (bool, error) {
	home, _ := os.UserHomeDir()
	removed := false
	for _, rc := range []string{".profile", ".zshrc", ".bashrc"} {
		f := filepath.Join(home, rc)
		data, err := os.ReadFile(f)
		if err != nil || !strings.Contains(string(data), pathLine) {
			continue
		}
		os.WriteFile(f, []byte(strings.ReplaceAll(string(data), "\n"+pathLine+"\n", "\n")), 0o644)
		removed = true
	}
	return removed, nil
}

func openedByDoubleClick() bool { return false }

func doubleClickWelcome() {}

func terminalName() string { return "o ejecuta: source ~/.profile" }
