package com.codeflow.app.core.network

import org.junit.Assert.assertEquals
import org.junit.Assert.assertNull
import org.junit.Test

class ServerUrlTest {

    @Test
    fun `ip y puerto se convierten en la url de la api`() {
        assertEquals("http://192.168.1.50:8080/api/", ServerUrl.normalize("192.168.1.50:8080"))
    }

    @Test
    fun `respeta el esquema, la barra final y el sufijo api`() {
        assertEquals("http://192.168.1.50:8080/api/", ServerUrl.normalize("http://192.168.1.50:8080/"))
        assertEquals("http://192.168.1.50:8080/api/", ServerUrl.normalize("192.168.1.50:8080/api"))
        assertEquals("https://codeflow.example.com/api/", ServerUrl.normalize("https://codeflow.example.com"))
    }

    @Test
    fun `entradas no validas devuelven null`() {
        assertNull(ServerUrl.normalize(""))
        assertNull(ServerUrl.normalize("   "))
        assertNull(ServerUrl.normalize("192.168.1.50 8080"))
    }

    @Test
    fun `display muestra solo host y puerto`() {
        assertEquals("192.168.1.50:8080", ServerUrl.display("http://192.168.1.50:8080/api/"))
        assertEquals("https://codeflow.example.com", ServerUrl.display("https://codeflow.example.com/api/"))
    }
}
