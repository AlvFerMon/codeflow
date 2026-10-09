package com.codeflow.app.core.ui.components

import androidx.compose.foundation.layout.Box
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ExitToApp
import androidx.compose.material.icons.filled.AccountCircle
import androidx.compose.material.icons.filled.Settings
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.DropdownMenu
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue

/** Menú de la cuenta (barra superior): usuario, servidor y cerrar sesión con confirmación. */
@Composable
fun AccountMenu(
    userName: String?,
    server: String,
    onLogout: () -> Unit,
) {
    var expanded by remember { mutableStateOf(false) }
    var confirmLogout by remember { mutableStateOf(false) }

    Box {
        IconButton(onClick = { expanded = true }) {
            Icon(Icons.Filled.AccountCircle, contentDescription = "Cuenta")
        }
        DropdownMenu(expanded = expanded, onDismissRequest = { expanded = false }) {
            DropdownMenuItem(
                text = { Text(userName ?: "Mi cuenta") },
                leadingIcon = { Icon(Icons.Filled.AccountCircle, contentDescription = null) },
                onClick = {},
                enabled = false,
            )
            DropdownMenuItem(
                text = { Text("Servidor: $server") },
                leadingIcon = { Icon(Icons.Filled.Settings, contentDescription = null) },
                onClick = {},
                enabled = false,
            )
            HorizontalDivider()
            DropdownMenuItem(
                text = { Text("Cerrar sesión") },
                leadingIcon = { Icon(Icons.AutoMirrored.Filled.ExitToApp, contentDescription = null) },
                onClick = {
                    expanded = false
                    confirmLogout = true
                },
            )
        }
    }

    if (confirmLogout) {
        AlertDialog(
            onDismissRequest = { confirmLogout = false },
            title = { Text("Cerrar sesión") },
            text = { Text("¿Seguro que quieres cerrar la sesión en este dispositivo? Dejarás de recibir avisos.") },
            confirmButton = {
                TextButton(onClick = {
                    confirmLogout = false
                    onLogout()
                }) { Text("Cerrar sesión") }
            },
            dismissButton = { TextButton(onClick = { confirmLogout = false }) { Text("Cancelar") } },
        )
    }
}
