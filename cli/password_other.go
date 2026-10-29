//go:build !windows

package main

import (
	"fmt"
	"os"
	"os/exec"
)

// readPassword lee la contraseña sin mostrarla (stty -echo en Linux y macOS).
func readPassword(label string) (string, error) {
	fmt.Print(label)
	off := exec.Command("stty", "-echo")
	off.Stdin = os.Stdin
	if off.Run() != nil {
		return prompt(""), nil // sin terminal (entrada redirigida)
	}
	defer func() {
		on := exec.Command("stty", "echo")
		on.Stdin = os.Stdin
		_ = on.Run()
		fmt.Println()
	}()
	return prompt(""), nil
}
