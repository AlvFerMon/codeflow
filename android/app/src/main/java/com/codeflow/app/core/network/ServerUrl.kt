package com.codeflow.app.core.network

import okhttp3.HttpUrl.Companion.toHttpUrlOrNull

/**
 * Dirección del servidor de CodeFlow que escribe el usuario (p. ej. `192.168.1.50:8080`).
 *
 * Se normaliza a la URL base de la API: `http://192.168.1.50:8080/api/`.
 * Sin esquema se asume `http://` (red local); se respeta `https://` si se escribe.
 */
object ServerUrl {

    /** Devuelve la URL base de la API (terminada en `/api/`) o `null` si no es válida. */
    fun normalize(input: String): String? {
        var value = input.trim().trimEnd('/')
        if (value.isEmpty() || value.any { it.isWhitespace() }) return null
        if (!value.startsWith("http://", ignoreCase = true) && !value.startsWith("https://", ignoreCase = true)) {
            value = "http://$value"
        }
        if (!value.endsWith("/api", ignoreCase = true)) value += "/api"
        val url = "$value/".toHttpUrlOrNull() ?: return null
        return if (url.host.isBlank()) null else url.toString()
    }

    /** Versión corta para mostrar: `http://192.168.1.50:8080/api/` → `192.168.1.50:8080`. */
    fun display(apiBaseUrl: String): String =
        apiBaseUrl
            .removePrefix("http://")
            .removeSuffix("/")
            .removeSuffix("/api")
}
