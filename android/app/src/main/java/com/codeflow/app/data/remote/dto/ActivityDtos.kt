package com.codeflow.app.data.remote.dto

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

/**
 * Elemento de GET /api/me/activity: quién hizo qué, en qué proyecto, repositorio y rama, y cuándo.
 * `summary` es texto plano sin el nombre del autor ("subió 2 cambios a la rama main").
 */
@Serializable
data class ActivityDto(
    val id: Long,
    val action: String,
    val category: String = "other",
    val summary: String = "",
    val actor: UserSummaryDto? = null,
    val project: ActivityProjectDto? = null,
    val repository: ActivityRepositoryDto? = null,
    val branch: String? = null,
    @SerialName("target_branch") val targetBranch: String? = null,
    @SerialName("commit_sha") val commitSha: String? = null,
    val number: Int? = null,
    @SerialName("created_at") val createdAt: String? = null,
)

@Serializable
data class ActivityProjectDto(
    val id: Long,
    val name: String? = null,
)

@Serializable
data class ActivityRepositoryDto(
    val id: Long,
    val name: String,
)
