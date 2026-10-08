package com.codeflow.app.data.remote

import com.codeflow.app.data.remote.dto.AuthResponse
import com.codeflow.app.data.remote.dto.LoginRequest
import com.codeflow.app.data.remote.dto.UnreadCountDto
import com.codeflow.app.data.remote.dto.UserDto
import kotlinx.serialization.json.JsonElement
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.Path
import retrofit2.http.Query

/**
 * Endpoints de la API REST de Laravel. Las rutas son relativas a BuildConfig.BASE_URL
 * (que ya termina en `/api/`), por eso NO empiezan por `/`.
 *
 * Cuando el backend pase a `/api/v1`, basta con cambiar BASE_URL en app/build.gradle.kts.
 */
interface CodeFlowApi {

    // ---- Autenticación ----

    @POST("auth/login")
    suspend fun login(@Body body: LoginRequest): AuthResponse

    @POST("auth/logout")
    suspend fun logout()

    @GET("me")
    suspend fun me(): UserDto

    // ---- Proyectos ----

    /** Paginado de Laravel (o array plano): se decodifica con `decodePage`. */
    @GET("projects")
    suspend fun projects(@Query("page") page: Int): JsonElement

    // ---- Notificaciones ----

    @GET("me/notifications")
    suspend fun notifications(@Query("page") page: Int): JsonElement

    @GET("me/notifications/unread-count")
    suspend fun unreadNotificationCount(): UnreadCountDto

    @POST("me/notifications/{id}/read")
    suspend fun markNotificationRead(@Path("id") id: String)

    // TODO(fases siguientes): repositorios, ramas, commits, issues, pull requests, revisiones.
}
