package com.codeflow.app.domain.model

/** Modelos de dominio: lo que consume la UI (independiente del JSON de la API). */

data class User(
    val id: Long,
    val name: String,
    val email: String,
    val emailVerified: Boolean = true,
)

data class Project(
    val id: Long,
    val name: String,
    val description: String?,
    val ownerName: String?,
    val updatedAt: String?,
)

enum class NotificationKind {
    /** Cambio en un proyecto (commit, PR, issue...). */
    CHANGE,

    /** Algo que requiere atención (conflicto, revisión pedida, permiso...). */
    ALERT,
    ;

    companion object {
        fun fromApi(value: String?): NotificationKind =
            if (value.equals("alert", ignoreCase = true)) ALERT else CHANGE
    }
}

data class AppNotification(
    val id: String,
    val kind: NotificationKind,
    val event: String?,
    val title: String,
    val message: String?,
    val url: String?,
    val isRead: Boolean,
    val createdAt: String?,
    val actorName: String?,
    val projectName: String?,
)

/** Tipo de evento, para el icono y el color de cada elemento del feed. */
enum class ActivityCategory {
    COMMIT, BRANCH, PULL_REQUEST, ISSUE, REPOSITORY, PROJECT, MEMBER, OTHER;

    companion object {
        fun fromApi(value: String?): ActivityCategory = when (value) {
            "commit" -> COMMIT
            "branch" -> BRANCH
            "pull_request", "review" -> PULL_REQUEST
            "issue" -> ISSUE
            "repository" -> REPOSITORY
            "project" -> PROJECT
            "member" -> MEMBER
            else -> OTHER
        }
    }
}

/** Un evento del feed de actividad: quién, qué, dónde (proyecto, repositorio, rama) y cuándo. */
data class ActivityItem(
    val id: Long,
    val action: String,
    val category: ActivityCategory,
    val actorName: String?,
    val summary: String,
    val projectId: Long?,
    val projectName: String?,
    val repositoryName: String?,
    val branch: String?,
    val targetBranch: String?,
    val commitSha: String?,
    val createdAt: String?,
)

/** Página de resultados de un listado paginado. */
data class Page<T>(
    val items: List<T>,
    val currentPage: Int,
    val lastPage: Int,
) {
    val hasMore: Boolean get() = currentPage < lastPage
}
