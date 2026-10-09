package com.codeflow.app.core.network

import okhttp3.HttpUrl.Companion.toHttpUrlOrNull
import okhttp3.Interceptor
import okhttp3.Response

/**
 * Permite cambiar el servidor sin recrear Retrofit: Retrofit construye las URLs sobre
 * [PLACEHOLDER_BASE_URL] y este interceptor las reescribe contra la URL configurada por el
 * usuario (p. ej. `http://192.168.1.50:8080/api/`). Debe ser el primer interceptor.
 */
class BaseUrlInterceptor(
    private val baseUrlProvider: () -> String,
) : Interceptor {

    override fun intercept(chain: Interceptor.Chain): Response {
        val request = chain.request()
        val original = request.url.toString()
        if (!original.startsWith(PLACEHOLDER_BASE_URL)) return chain.proceed(request)

        val base = baseUrlProvider().toHttpUrlOrNull() ?: return chain.proceed(request)
        val target = base.resolve(original.removePrefix(PLACEHOLDER_BASE_URL)) ?: return chain.proceed(request)

        return chain.proceed(request.newBuilder().url(target).build())
    }

    companion object {
        /** Dominio reservado (.invalid nunca resuelve): si algo falla, la petición no sale a Internet. */
        const val PLACEHOLDER_BASE_URL = "http://codeflow.invalid/api/"
    }
}
