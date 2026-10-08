package com.codeflow.app.data.remote.dto

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

/** Elemento de GET /api/projects (paginado, con `owner:id,name`). */
@Serializable
data class ProjectDto(
    val id: Long,
    val name: String,
    val description: String? = null,
    @SerialName("owner_id") val ownerId: Long? = null,
    val owner: UserSummaryDto? = null,
    @SerialName("created_at") val createdAt: String? = null,
    @SerialName("updated_at") val updatedAt: String? = null,
)

/** Proyecto resumido (p. ej. dentro de una notificación). */
@Serializable
data class ProjectSummaryDto(
    val id: Long,
    val name: String,
)
