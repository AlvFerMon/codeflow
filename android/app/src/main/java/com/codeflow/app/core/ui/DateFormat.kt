package com.codeflow.app.core.ui

import java.time.Duration
import java.time.Instant
import java.time.OffsetDateTime
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
    val instant = parseApiInstant(iso) ?: return iso
    return displayFormatter.format(instant.atZone(zone))
}

/**
 * Hora relativa en español para los listados: "ahora", "hace 5 min", "hace 3 h", "hace 2 d";
 * a partir de una semana, la fecha completa.
 */
fun formatRelativeDateTime(iso: String?, now: Instant = Instant.now(), zone: ZoneId = ZoneId.systemDefault()): String {
    if (iso.isNullOrBlank()) return ""
    val instant = parseApiInstant(iso) ?: return iso
    val seconds = Duration.between(instant, now).seconds
    return when {
        seconds < 60 -> "ahora"
        seconds < 3_600 -> "hace ${seconds / 60} min"
        seconds < 86_400 -> "hace ${seconds / 3_600} h"
        seconds < 7 * 86_400 -> "hace ${seconds / 86_400} d"
        else -> displayFormatter.format(instant.atZone(zone))
    }
}

/** Acepta `2026-10-08T12:34:56.000000Z` y también con desfase (`2026-10-08T14:34:56+02:00`). */
internal fun parseApiInstant(iso: String): Instant? =
    runCatching { Instant.parse(iso) }.getOrNull()
        ?: runCatching { OffsetDateTime.parse(iso).toInstant() }.getOrNull()
