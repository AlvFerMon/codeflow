package com.codeflow.app.domain.model

/** Modelos de dominio: lo que consume la UI (independiente del JSON de la API). */

data class User(
    val id: Long,
    val name: String,
    val email: String,
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

/** Página de resultados de un listado paginado. */
data class Page<T>(
    val items: List<T>,
    val currentPage: Int,
    val lastPage: Int,
) {
    val hasMore: Boolean get() = currentPage < lastPage
}
