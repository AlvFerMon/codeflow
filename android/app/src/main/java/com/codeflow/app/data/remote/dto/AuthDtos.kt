package com.codeflow.app.data.remote.dto

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

/** POST /api/auth/login */
@Serializable
data class LoginRequest(
    val email: String,
    val password: String,
    @SerialName("device_name") val deviceName: String,
)

/** Respuesta de login/registro: `{ "user": {...}, "token": "1|abc..." }` */
@Serializable
data class AuthResponse(
    val user: UserDto,
    val token: String,
)

/** Usuario tal como lo serializa Laravel (modelo User sin password ni remember_token). */
@Serializable
data class UserDto(
    val id: Long,
    val name: String,
    val email: String,
    val role: String? = null,
    @SerialName("email_verified_at") val emailVerifiedAt: String? = null,
    @SerialName("created_at") val createdAt: String? = null,
    @SerialName("updated_at") val updatedAt: String? = null,
)

/** Usuario resumido (relaciones cargadas con `with('owner:id,name')`, `actor`...). */
@Serializable
data class UserSummaryDto(
    val id: Long,
    val name: String,
)
