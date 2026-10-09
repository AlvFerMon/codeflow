package com.codeflow.app.data.repository

import com.codeflow.app.core.network.ApiResult
import com.codeflow.app.core.network.safeApiCall
import com.codeflow.app.core.session.SessionStore
import com.codeflow.app.data.remote.CodeFlowApi
import com.codeflow.app.data.remote.dto.LoginRequest
import com.codeflow.app.data.remote.dto.VerifyEmailRequest
import com.codeflow.app.domain.model.User

interface AuthRepository {
    /**
     * Inicia sesión y, si va bien, guarda el token en la sesión. Si el email no está verificado,
     * la sesión queda pendiente de introducir el código (la navegación muestra esa pantalla).
     */
    suspend fun login(email: String, password: String, deviceName: String): ApiResult<User>

    /** Envía el código de 6 dígitos recibido por correo. */
    suspend fun verifyEmail(code: String): ApiResult<Unit>

    /** Pide un código nuevo. Devuelve el mensaje del servidor ("Te hemos enviado un código..."). */
    suspend fun resendVerificationCode(): ApiResult<String>

    /** Revoca el token en el servidor (si se puede) y borra la sesión local siempre. */
    suspend fun logout()
}

class DefaultAuthRepository(
    private val api: CodeFlowApi,
    private val sessionStore: SessionStore,
) : AuthRepository {

    override suspend fun login(email: String, password: String, deviceName: String): ApiResult<User> =
        when (val result = safeApiCall { api.login(LoginRequest(email, password, deviceName)) }) {
            is ApiResult.Success -> {
                val user = result.data.user.toDomain()
                sessionStore.saveSession(result.data.token, user.name, user.emailVerified)
                ApiResult.Success(user)
            }
            is ApiResult.Error -> result
        }

    override suspend fun verifyEmail(code: String): ApiResult<Unit> =
        when (val result = safeApiCall { api.verifyEmail(VerifyEmailRequest(code)) }) {
            is ApiResult.Success -> {
                sessionStore.markEmailVerified()
                ApiResult.Success(Unit)
            }
            is ApiResult.Error -> result
        }

    override suspend fun resendVerificationCode(): ApiResult<String> = safeApiCall {
        api.resendVerificationCode().message ?: "Te hemos enviado un código nuevo."
    }

    override suspend fun logout() {
        // Aunque falle la red, la sesión local se cierra igualmente.
        safeApiCall { api.logout() }
        sessionStore.clear()
    }
}
