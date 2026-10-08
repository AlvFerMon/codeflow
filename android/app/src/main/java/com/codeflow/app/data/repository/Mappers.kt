package com.codeflow.app.data.repository

import com.codeflow.app.data.remote.dto.NotificationDto
import com.codeflow.app.data.remote.dto.PageDto
import com.codeflow.app.data.remote.dto.ProjectDto
import com.codeflow.app.data.remote.dto.UserDto
import com.codeflow.app.domain.model.AppNotification
import com.codeflow.app.domain.model.NotificationKind
import com.codeflow.app.domain.model.Page
import com.codeflow.app.domain.model.Project
import com.codeflow.app.domain.model.User

internal fun UserDto.toDomain() = User(id = id, name = name, email = email)

internal fun ProjectDto.toDomain() = Project(
    id = id,
    name = name,
    description = description,
    ownerName = owner?.name,
    updatedAt = updatedAt,
)

internal fun NotificationDto.toDomain() = AppNotification(
    id = id,
    kind = NotificationKind.fromApi(kind),
    event = event,
    title = title,
    message = message,
    url = url,
    isRead = readAt != null,
    createdAt = createdAt,
    actorName = actor?.name,
    projectName = project?.name,
)

internal fun <T, R> PageDto<T>.toDomain(transform: (T) -> R) = Page(
    items = data.map(transform),
    currentPage = currentPage,
    lastPage = lastPage,
)
