package com.codeflow.app.data.repository

import com.codeflow.app.core.network.ApiResult
import com.codeflow.app.core.network.safeApiCall
import com.codeflow.app.core.session.SessionStore
import com.codeflow.app.data.remote.CodeFlowApi
import com.codeflow.app.data.remote.dto.LoginRequest
import com.codeflow.app.domain.model.User

interface AuthRepository {
    /** Inicia sesión y, si va bien, guarda el token en la sesión. */
    suspend fun login(email: String, password: String, deviceName: String): ApiResult<User>

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
                sessionStore.saveSession(result.data.token, result.data.user.name)
                ApiResult.Success(result.data.user.toDomain())
            }
            is ApiResult.Error -> result
        }

    override suspend fun logout() {
        // Aunque falle la red, la sesión local se cierra igualmente.
        safeApiCall { api.logout() }
        sessionStore.clear()
    }
}
