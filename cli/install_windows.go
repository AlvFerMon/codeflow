//go:build windows

package main

import (
	"fmt"
	"os"
	"path/filepath"
	"strings"
	"syscall"
	"unsafe"
)

func binaryName() string { return "codeflow.exe" }

// %LOCALAPPDATA%\Programs\CodeFlow: donde instalan los programas "solo para mí" (no pide administrador)
func installDir() string {
	base := os.Getenv("LOCALAPPDATA")
	if base == "" {
		home, _ := os.UserHomeDir()
		base = filepath.Join(home, "AppData", "Local")
	}
	return filepath.Join(base, "Programs", "CodeFlow")
}

var (
	advapi32          = syscall.NewLazyDLL("advapi32.dll")
	regSetValueEx     = advapi32.NewProc("RegSetValueExW")
	user32            = syscall.NewLazyDLL("user32.dll")
	sendMessageTimout = user32.NewProc("SendMessageTimeoutW")
	kernel32          = syscall.NewLazyDLL("kernel32.dll")
	getConsoleProcs   = kernel32.NewProc("GetConsoleProcessList")
)

const (
	regExpandSz     = 2
	hwndBroadcast   = 0xffff
	wmSettingChange = 0x001A
	smtoAbortIfHung = 0x0002
	keySetValue     = 0x0002
	keyQueryValue   = 0x0001
)

// PATH del usuario (HKCU\Environment\Path), no el del sistema: no hace falta ser administrador
func userPath() (string, syscall.Handle, error) {
	var key syscall.Handle
	name, _ := syscall.UTF16PtrFromString("Environment")
	if err := syscall.RegOpenKeyEx(syscall.HKEY_CURRENT_USER, name, 0, keyQueryValue|keySetValue, &key); err != nil {
		return "", 0, err
	}
	value, _ := syscall.UTF16PtrFromString("Path")
	var typ, size uint32
	if err := syscall.RegQueryValueEx(key, value, nil, &typ, nil, &size); err != nil || size == 0 {
		return "", key, nil // aún no existe
	}
	buf := make([]uint16, size/2+1)
	if err := syscall.RegQueryValueEx(key, value, nil, &typ, (*byte)(unsafe.Pointer(&buf[0])), &size); err != nil {
		syscall.RegCloseKey(key)
		return "", 0, err
	}
	return syscall.UTF16ToString(buf), key, nil
}

func setUserPath(key syscall.Handle, path string) error {
	value, _ := syscall.UTF16PtrFromString("Path")
	data, _ := syscall.UTF16FromString(path)
	r, _, _ := regSetValueEx.Call(uintptr(key), uintptr(unsafe.Pointer(value)), 0, regExpandSz,
		uintptr(unsafe.Pointer(&data[0])), uintptr(len(data)*2))
	if r != 0 {
		return syscall.Errno(r)
	}
	// Avisa a Windows (Explorador, terminales nuevas) de que el entorno ha cambiado
	env, _ := syscall.UTF16PtrFromString("Environment")
	var result uintptr
	sendMessageTimout.Call(hwndBroadcast, wmSettingChange, 0, uintptr(unsafe.Pointer(env)), smtoAbortIfHung, 5000, uintptr(unsafe.Pointer(&result)))
	return nil
}

func addToPath(dir string) (bool, error) {
	current, key, err := userPath()
	if err != nil {
		return false, err
	}
	defer syscall.RegCloseKey(key)
	if pathContains(current, dir) {
		return false, nil
	}
	next := strings.TrimRight(current, ";")
	if next != "" {
		next += ";"
	}
	return true, setUserPath(key, next+dir)
}

func removeFromPath(dir string) (bool, error) {
	current, key, err := userPath()
	if err != nil {
		return false, err
	}
	defer syscall.RegCloseKey(key)
	var keep []string
	removed := false
	for _, p := range strings.Split(current, ";") {
		if p == "" {
			continue
		}
		if samePath(strings.TrimRight(os.ExpandEnv(p), `\`), dir) {
			removed = true
			continue
		}
		keep = append(keep, p)
	}
	if !removed {
		return false, nil
	}
	return true, setUserPath(key, strings.Join(keep, ";"))
}

// openedByDoubleClick: si la consola solo tiene este proceso, se abrió desde el Explorador
// (al terminar se cerraría la ventana sin dar tiempo a leer nada)
func openedByDoubleClick() bool {
	var ids [2]uint32
	n, _, _ := getConsoleProcs.Call(uintptr(unsafe.Pointer(&ids[0])), 2)
	return n == 1
}

// doubleClickWelcome: lo que ve alguien que abre codeflow.exe desde el Explorador
func doubleClickWelcome() {
	fmt.Println("CodeFlow", Version, "— trabaja con tus repositorios desde la terminal.")
	fmt.Println()
	if isInstalled() {
		fmt.Println("Ya está instalado. Abre una terminal (PowerShell o cmd) y escribe: codeflow help")
	} else {
		answer := strings.ToLower(prompt("¿Instalar CodeFlow para usarlo desde la terminal? [S/n] "))
		fmt.Println()
		if answer == "" || answer == "s" || answer == "si" || answer == "sí" || answer == "y" {
			if err := cmdInstall(nil); err != nil {
				fmt.Println("Error:", err)
			}
		} else {
			fmt.Println("No se ha instalado. Puedes hacerlo cuando quieras con: codeflow install")
		}
	}
	fmt.Println()
	prompt("Pulsa Enter para cerrar…")
}

func terminalName() string { return "PowerShell o cmd" }
