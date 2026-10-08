package com.codeflow.app.core.network

import java.io.IOException
import kotlin.coroutines.cancellation.CancellationException
import kotlinx.serialization.Serializable
import kotlinx.serialization.SerializationException
import kotlinx.serialization.json.Json
import retrofit2.HttpException

/**
 * Resultado de una llamada a la API: los repositorios nunca lanzan excepciones hacia la UI.
 */
sealed interface ApiResult<out T> {

    data class Success<out T>(val data: T) : ApiResult<T>

    /**
     * @param code código HTTP, o `null` si no hubo respuesta (sin red, timeout, JSON inválido...).
     * @param message mensaje listo para mostrar al usuario (en español).
     * @param fieldErrors errores de validación de Laravel (422): campo → mensajes.
     */
    data class Error(
        val code: Int?,
        val message: String,
        val fieldErrors: Map<String, List<String>> = emptyMap(),
    ) : ApiResult<Nothing> {
        val isNetworkError: Boolean get() = code == null
        fun firstErrorFor(field: String): String? = fieldErrors[field]?.firstOrNull()
    }
}

inline fun <T, R> ApiResult<T>.map(transform: (T) -> R): ApiResult<R> = when (this) {
    is ApiResult.Success -> ApiResult.Success(transform(data))
    is ApiResult.Error -> this
}

/** Cuerpo de error estándar de Laravel: `{"message": "...", "errors": {"campo": ["..."]}}`. */
@Serializable
private data class LaravelErrorBody(
    val message: String? = null,
    val errors: Map<String, List<String>> = emptyMap(),
)

const val MSG_NETWORK = "No se pudo conectar con el servidor. Comprueba tu conexión."
const val MSG_UNEXPECTED_RESPONSE = "El servidor ha enviado una respuesta inesperada."

/**
 * Convierte una respuesta de error de la API en [ApiResult.Error].
 *
 * Para 401, 404, 429 y 5xx se usa siempre un mensaje propio en español (los de Laravel por
 * defecto vienen en inglés: "Unauthenticated.", "Too Many Attempts.", "Server Error"...).
 * Para el resto (403, 409, 422...) se usa el `message` del servidor si existe.
 */
fun parseLaravelError(code: Int, body: String?, json: Json = ApiClient.json): ApiResult.Error {
    val parsed = body
        ?.takeIf { it.isNotBlank() }
        ?.let { runCatching { json.decodeFromString(LaravelErrorBody.serializer(), it) }.getOrNull() }

    val serverMessage = parsed?.message?.takeIf { it.isNotBlank() }
    val message = when {
        code == 401 -> "Tu sesión ha caducado. Vuelve a iniciar sesión."
        code == 404 -> "No se ha encontrado el recurso solicitado."
        code == 429 -> "Demasiados intentos. Espera un momento y vuelve a probar."
        code >= 500 -> "Error del servidor ($code). Inténtalo más tarde."
        serverMessage != null -> serverMessage
        code == 403 -> "No tienes permiso para realizar esta acción."
        code == 422 -> "Revisa los datos introducidos."
        else -> "Error inesperado ($code)."
    }
    return ApiResult.Error(code = code, message = message, fieldErrors = parsed?.errors.orEmpty())
}

/**
 * Ejecuta una llamada Retrofit y convierte cualquier fallo en [ApiResult.Error].
 * Las cancelaciones de corrutinas se relanzan (no son errores).
 */
suspend fun <T> safeApiCall(json: Json = ApiClient.json, call: suspend () -> T): ApiResult<T> =
    try {
        ApiResult.Success(call())
    } catch (e: CancellationException) {
        throw e
    } catch (e: HttpException) {
        // Retrofit ya ha leído (buffer) el cuerpo de error, así que leerlo aquí no bloquea la red.
        val body = runCatching { e.response()?.errorBody()?.string() }.getOrNull()
        parseLaravelError(e.code(), body, json)
    } catch (e: IOException) {
        ApiResult.Error(code = null, message = MSG_NETWORK)
    } catch (e: SerializationException) {
        ApiResult.Error(code = null, message = MSG_UNEXPECTED_RESPONSE)
    }
