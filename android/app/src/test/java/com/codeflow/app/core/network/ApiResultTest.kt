package com.codeflow.app.core.network

import java.io.IOException
import kotlinx.coroutines.test.runTest
import kotlinx.serialization.SerializationException
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.ResponseBody.Companion.toResponseBody
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Test
import retrofit2.HttpException
import retrofit2.Response

class ApiResultTest {

    @Test
    fun `parsea el error 422 de login de Laravel`() {
        val body = """
            {"message":"Credenciales incorrectas.","errors":{"email":["Credenciales incorrectas."]}}
        """.trimIndent()

        val error = parseLaravelError(422, body)

        assertEquals(422, error.code)
        assertEquals("Credenciales incorrectas.", error.message)
        assertEquals("Credenciales incorrectas.", error.firstErrorFor("email"))
        assertNull(error.firstErrorFor("password"))
    }

    @Test
    fun `parsea varios campos con varios mensajes`() {
        val body = """
            {
              "message": "The name field is required. (and 2 more errors)",
              "errors": {
                "name": ["El nombre es obligatorio."],
                "password": ["Mínimo 8 caracteres.", "Las contraseñas no coinciden."]
              }
            }
        """.trimIndent()

        val error = parseLaravelError(422, body)

        assertEquals(2, error.fieldErrors.size)
        assertEquals("El nombre es obligatorio.", error.firstErrorFor("name"))
        assertEquals(2, error.fieldErrors.getValue("password").size)
    }

    @Test
    fun `usa el mensaje del servidor en 403 y 409`() {
        assertEquals("No eres miembro.", parseLaravelError(403, """{"message":"No eres miembro."}""").message)
        assertEquals("La rama ha cambiado.", parseLaravelError(409, """{"message":"La rama ha cambiado."}""").message)
    }

    @Test
    fun `usa mensajes propios en español para 401, 429 y 5xx`() {
        assertEquals(
            "Tu sesión ha caducado. Vuelve a iniciar sesión.",
            parseLaravelError(401, """{"message":"Unauthenticated."}""").message,
        )
        assertTrue(parseLaravelError(429, """{"message":"Too Many Attempts."}""").message.startsWith("Demasiados intentos"))
        assertTrue(parseLaravelError(500, """{"message":"Server Error"}""").message.contains("500"))
    }

    @Test
    fun `tolera cuerpos vacios o que no son JSON`() {
        val html = parseLaravelError(422, "<html><body>Error</body></html>")
        assertEquals("Revisa los datos introducidos.", html.message)
        assertTrue(html.fieldErrors.isEmpty())

        assertEquals("No tienes permiso para realizar esta acción.", parseLaravelError(403, null).message)
        assertEquals("Error inesperado (418).", parseLaravelError(418, "").message)
    }

    @Test
    fun `safeApiCall devuelve Success con el resultado`() = runTest {
        val result = safeApiCall { 42 }
        assertEquals(ApiResult.Success(42), result)
    }

    @Test
    fun `safeApiCall convierte HttpException en Error con los errores de campo`() = runTest {
        val body = """{"message":"Credenciales incorrectas.","errors":{"email":["Credenciales incorrectas."]}}"""
            .toResponseBody("application/json".toMediaType())

        val result = safeApiCall<Unit> { throw HttpException(Response.error<Unit>(422, body)) }

        result as ApiResult.Error
        assertEquals(422, result.code)
        assertEquals("Credenciales incorrectas.", result.firstErrorFor("email"))
    }

    @Test
    fun `safeApiCall convierte IOException en error de red`() = runTest {
        val result = safeApiCall<Unit> { throw IOException("timeout") }

        result as ApiResult.Error
        assertNull(result.code)
        assertTrue(result.isNetworkError)
        assertEquals(MSG_NETWORK, result.message)
    }

    @Test
    fun `safeApiCall convierte un JSON inesperado en error`() = runTest {
        val result = safeApiCall<Unit> { throw SerializationException("campo id ausente") }

        result as ApiResult.Error
        assertEquals(MSG_UNEXPECTED_RESPONSE, result.message)
    }
}
