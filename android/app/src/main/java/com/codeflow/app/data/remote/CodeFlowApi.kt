package com.codeflow.app.data.remote

import com.codeflow.app.data.remote.dto.AuthResponse
import com.codeflow.app.data.remote.dto.LoginRequest
import com.codeflow.app.data.remote.dto.MessageDto
import com.codeflow.app.data.remote.dto.UnreadCountDto
import com.codeflow.app.data.remote.dto.UserDto
import com.codeflow.app.data.remote.dto.VerifyEmailRequest
import kotlinx.serialization.json.JsonElement
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.Path
import retrofit2.http.Query

/**
 * Endpoints de la API REST de Laravel. Las rutas son relativas a la URL base (`.../api/`),
 * por eso NO empiezan por `/`. El servidor real lo decide el BaseUrlInterceptor.
 * Los parámetros `@Query` a `null` no se envían.
 */
interface CodeFlowApi {

    // ---- Autenticación ----

    @POST("auth/login")
    suspend fun login(@Body body: LoginRequest): AuthResponse

    @POST("auth/logout")
    suspend fun logout()

    @POST("auth/email/verify")
    suspend fun verifyEmail(@Body body: VerifyEmailRequest): MessageDto

    @POST("auth/email/verification-notification")
    suspend fun resendVerificationCode(): MessageDto

    @GET("me")
    suspend fun me(): UserDto

    // ---- Actividad ----

    /** Actividad de todos mis proyectos, de la más reciente a la más antigua (paginado de 30). */
    @GET("me/activity")
    suspend fun activity(@Query("page") page: Int, @Query("project_id") projectId: Long?): JsonElement

    // ---- Proyectos ----

    /** Paginado de Laravel (o array plano): se decodifica con `decodePage`. */
    @GET("projects")
    suspend fun projects(@Query("page") page: Int): JsonElement

    // ---- Notificaciones ----

    /** @param unread `1` para pedir solo las no leídas. */
    @GET("me/notifications")
    suspend fun notifications(@Query("page") page: Int, @Query("unread") unread: Int?): JsonElement

    @GET("me/notifications/unread-count")
    suspend fun unreadNotificationCount(): UnreadCountDto

    @POST("me/notifications/{id}/read")
    suspend fun markNotificationRead(@Path("id") id: String)

    @POST("me/notifications/read-all")
    suspend fun markAllNotificationsRead()
}
