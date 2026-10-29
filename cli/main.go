// codeflow: cliente de línea de comandos de CodeFlow (clone, status, commit, push, pull, branch...).
// Habla con la API REST del servidor; no usa Git. Ver cli/README.md.
package main

import (
	"fmt"
	"os"
)

var Version = "1.0.0"

const usage = `CodeFlow CLI %s — trabaja con tus repositorios de CodeFlow desde la terminal.

Uso: codeflow <comando> [opciones]

  login [servidor]              Inicia sesión (te pide correo y contraseña)
  logout                        Cierra la sesión
  whoami                        Muestra con qué cuenta estás conectado

  clone <url> [carpeta] [-b rama]
                                Descarga un repositorio (url de su página: …/repositories/12)
  status                        Archivos nuevos, modificados y borrados
  commit -m "mensaje"           Confirma todos los cambios en un commit local
  push                          Sube tus commits al servidor
  pull                          Descarga los cambios de los demás
  restore <archivo>|.           Deshace los cambios sin confirmar
  log                           Historial de la rama
  branch [nombre]               Lista las ramas o crea una nueva
  checkout <rama>               Cambia de rama

  version                       Versión de la CLI

Los archivos que no quieras subir se ponen en .codeflowignore (como .gitignore).
`

func main() {
	if len(os.Args) < 2 {
		fmt.Printf(usage, Version)
		return
	}
	commands := map[string]func([]string) error{
		"login": cmdLogin, "logout": cmdLogout, "whoami": cmdWhoami,
		"clone": cmdClone, "status": cmdStatus, "commit": cmdCommit,
		"push": cmdPush, "pull": cmdPull, "restore": cmdRestore,
		"log": cmdLog, "branch": cmdBranch, "checkout": cmdCheckout,
	}
	name := os.Args[1]
	switch name {
	case "help", "-h", "--help":
		fmt.Printf(usage, Version)
		return
	case "version", "-v", "--version":
		fmt.Println("codeflow", Version)
		return
	}
	cmd, ok := commands[name]
	if !ok {
		fmt.Fprintf(os.Stderr, "Comando desconocido: %s\n\n", name)
		fmt.Printf(usage, Version)
		os.Exit(2)
	}
	if err := cmd(os.Args[2:]); err != nil {
		fmt.Fprintln(os.Stderr, "Error:", err)
		os.Exit(1)
	}
}
