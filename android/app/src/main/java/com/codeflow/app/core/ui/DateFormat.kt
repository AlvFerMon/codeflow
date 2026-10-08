package com.codeflow.app.core.ui

import java.time.Instant
import java.time.ZoneId
import java.time.format.DateTimeFormatter
import java.util.Locale

private val displayFormatter: DateTimeFormatter =
    DateTimeFormatter.ofPattern("d MMM yyyy, HH:mm", Locale.forLanguageTag("es-ES"))

/**
 * Formatea las fechas ISO-8601 de Laravel (`2026-10-08T12:34:56.000000Z`) en hora local.
 * Si no se puede interpretar, devuelve el texto original.
 */
fun formatApiDateTime(iso: String?, zone: ZoneId = ZoneId.systemDefault()): String {
    if (iso.isNullOrBlank()) return ""
    return runCatching { displayFormatter.format(Instant.parse(iso).atZone(zone)) }
        .getOrDefault(iso)
}
