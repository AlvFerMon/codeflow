package com.codeflow.app.core.network

import okhttp3.Interceptor
import okhttp3.Response

/**
 * Añade a todas las peticiones:
 *  - `Accept: application/json` → Laravel responde los errores (401, 422...) en JSON y no con
 *    redirecciones HTML.
 *  - `Authorization: Bearer <token>` si hay sesión (token personal de Sanctum).
 */
class AuthInterceptor(
    private val tokenProvider: () -> String?,
) : Interceptor {

    override fun intercept(chain: Interceptor.Chain): Response {
        val builder = chain.request().newBuilder()
            .header("Accept", "application/json")

        val token = tokenProvider()
        if (!token.isNullOrBlank()) {
            builder.header("Authorization", "Bearer $token")
        }

        return chain.proceed(builder.build())
    }
}
