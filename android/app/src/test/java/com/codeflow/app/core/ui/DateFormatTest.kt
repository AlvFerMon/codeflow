package com.codeflow.app.core.ui

import java.time.Instant
import java.time.ZoneOffset
import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Test

class DateFormatTest {

    private val now = Instant.parse("2026-10-09T12:00:00Z")

    @Test
    fun `hora relativa`() {
        assertEquals("ahora", formatRelativeDateTime("2026-10-09T11:59:30.000000Z", now))
        assertEquals("hace 5 min", formatRelativeDateTime("2026-10-09T11:55:00.000000Z", now))
        assertEquals("hace 3 h", formatRelativeDateTime("2026-10-09T09:00:00.000000Z", now))
        assertEquals("hace 2 d", formatRelativeDateTime("2026-10-07T12:00:00.000000Z", now))
    }

    @Test
    fun `acepta fechas con desfase horario`() {
        assertEquals("hace 1 h", formatRelativeDateTime("2026-10-09T13:00:00+02:00", now))
    }

    @Test
    fun `fechas de mas de una semana se muestran completas`() {
        val text = formatRelativeDateTime("2026-10-01T12:00:00Z", now, ZoneOffset.UTC)
        assertTrue(text, text.startsWith("1 ") && text.endsWith("2026, 12:00"))
    }

    @Test
    fun `texto no valido se devuelve tal cual`() {
        assertEquals("ayer", formatRelativeDateTime("ayer", now))
        assertEquals("", formatRelativeDateTime(null, now))
    }
}
