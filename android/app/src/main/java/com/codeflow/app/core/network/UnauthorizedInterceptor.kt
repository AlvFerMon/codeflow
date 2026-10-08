package com.codeflow.app.core.network

import okhttp3.Interceptor
import okhttp3.Response

/**
 * Si una petición autenticada recibe **401** (token caducado o revocado), avisa a la sesión
 * para que borre el token y emita el evento de cierre de sesión; la navegación vuelve al Login.
 *
 * Solo actúa si la petición llevaba token: un 401 sin token (p. ej. durante el login) no
 * significa que la sesión haya caducado.
 */
class UnauthorizedInterceptor(
    private val onUnauthorized: () -> Unit,
) : Interceptor {

    override fun intercept(chain: Interceptor.Chain): Response {
        val request = chain.request()
        val response = chain.proceed(request)

        if (response.code == 401 && request.header("Authorization") != null) {
            onUnauthorized()
        }
        return response
    }
}
