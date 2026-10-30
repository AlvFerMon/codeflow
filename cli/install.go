package main

import (
	"fmt"
	"io"
	"os"
	"path/filepath"
	"strings"
)

// ── install / uninstall ─────────────────────────────────────────────────────
// Copia el ejecutable a una carpeta del usuario y la añade al PATH, sin permisos de administrador.
// En Windows también se hace al abrir codeflow.exe con doble clic (ver main.go).

func cmdInstall([]string) error {
	exe, err := os.Executable()
	if err != nil {
		return err
	}
	exe, _ = filepath.EvalSymlinks(exe)
	dir := installDir()
	dst := filepath.Join(dir, binaryName())

	if !samePath(exe, dst) {
		if err := os.MkdirAll(dir, 0o755); err != nil {
			return err
		}
		if err := copyFile(exe, dst); err != nil {
			return fmt.Errorf("no se pudo copiar a %s: %w", dir, err)
		}
	}

	added, err := addToPath(dir)
	if err != nil {
		return fmt.Errorf("copiado en %s, pero no se pudo añadir al PATH: %w", dir, err)
	}

	fmt.Println("CodeFlow está instalado en", dir)
	if added {
		fmt.Println("Se ha añadido esa carpeta a tu PATH.")
	} else {
		fmt.Println("La carpeta ya estaba en tu PATH.")
	}
	fmt.Println()
	fmt.Println("Siguiente paso: abre una terminal NUEVA ("+terminalName()+") y escribe:")
	fmt.Println("    codeflow login")
	fmt.Println("    codeflow repos")
	return nil
}

func cmdUninstall([]string) error {
	dir := installDir()
	removed, err := removeFromPath(dir)
	if err != nil {
		return err
	}
	if removed {
		fmt.Println("Se ha quitado", dir, "del PATH.")
	}
	dst := filepath.Join(dir, binaryName())
	if err := os.Remove(dst); err != nil && !os.IsNotExist(err) {
		fmt.Println("No se pudo borrar", dst, "(¿se está usando ahora?). Bórralo a mano si quieres.")
	} else {
		fmt.Println("CodeFlow se ha desinstalado. Tu sesión y tus carpetas clonadas no se tocan.")
	}
	return nil
}

func copyFile(src, dst string) error {
	in, err := os.Open(src)
	if err != nil {
		return err
	}
	defer in.Close()
	tmp := dst + ".nuevo"
	out, err := os.OpenFile(tmp, os.O_CREATE|os.O_TRUNC|os.O_WRONLY, 0o755)
	if err != nil {
		return err
	}
	if _, err := io.Copy(out, in); err != nil {
		out.Close()
		return err
	}
	if err := out.Close(); err != nil {
		return err
	}
	// En Windows no se puede sobrescribir un .exe en uso, pero sí renombrarlo
	if _, err := os.Stat(dst); err == nil {
		old := dst + ".viejo"
		os.Remove(old)
		if err := os.Rename(dst, old); err != nil {
			return err
		}
	}
	return os.Rename(tmp, dst)
}

func samePath(a, b string) bool {
	return strings.EqualFold(filepath.Clean(a), filepath.Clean(b))
}

// pathContains: ¿la lista (separada por ; o :) ya incluye la carpeta?
func pathContains(list, dir string) bool {
	for _, p := range filepath.SplitList(list) {
		if samePath(strings.TrimRight(os.ExpandEnv(p), `\/`), strings.TrimRight(dir, `\/`)) {
			return true
		}
	}
	return false
}

// isInstalled: el ejecutable que se está usando es el instalado y su carpeta está en el PATH
func isInstalled() bool {
	exe, err := os.Executable()
	if err != nil {
		return false
	}
	return samePath(exe, filepath.Join(installDir(), binaryName())) && pathContains(os.Getenv("PATH"), installDir())
}
