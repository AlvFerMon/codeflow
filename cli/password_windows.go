//go:build windows

package main

import (
	"fmt"
	"os"
	"syscall"
)

// readPassword lee la contraseña sin mostrarla (desactiva el eco de la consola de Windows).
func readPassword(label string) (string, error) {
	fmt.Print(label)
	handle := syscall.Handle(os.Stdin.Fd())
	var mode uint32
	if err := syscall.GetConsoleMode(handle, &mode); err != nil {
		// No es una consola (p. ej. entrada redirigida): se lee tal cual
		return prompt(""), nil
	}
	setConsoleMode := syscall.NewLazyDLL("kernel32.dll").NewProc("SetConsoleMode")
	const enableEchoInput = 0x0004
	setConsoleMode.Call(uintptr(handle), uintptr(mode&^enableEchoInput))
	defer setConsoleMode.Call(uintptr(handle), uintptr(mode))
	password := prompt("")
	fmt.Println()
	return password, nil
}
