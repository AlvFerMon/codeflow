package com.codeflow.app.data.repository

import com.codeflow.app.core.network.ApiClient
import com.codeflow.app.core.network.ApiResult
import com.codeflow.app.core.network.safeApiCall
import com.codeflow.app.data.remote.CodeFlowApi
import com.codeflow.app.data.remote.dto.NotificationDto
import com.codeflow.app.data.remote.dto.decodePage
import com.codeflow.app.domain.model.AppNotification
import com.codeflow.app.domain.model.Page

interface NotificationRepository {
    suspend fun getNotifications(page: Int = 1): ApiResult<Page<AppNotification>>
    suspend fun getUnreadCount(): ApiResult<Int>
    suspend fun markAsRead(id: String): ApiResult<Unit>
}

// TODO(fase 19): las notificaciones push (FCM) actualizarán el contador sin necesidad de recargar.
class DefaultNotificationRepository(
    private val api: CodeFlowApi,
) : NotificationRepository {

    override suspend fun getNotifications(page: Int): ApiResult<Page<AppNotification>> = safeApiCall {
        ApiClient.json
            .decodePage(api.notifications(page), NotificationDto.serializer())
            .toDomain { it.toDomain() }
    }

    override suspend fun getUnreadCount(): ApiResult<Int> = safeApiCall {
        api.unreadNotificationCount().count
    }

    override suspend fun markAsRead(id: String): ApiResult<Unit> = safeApiCall {
        api.markNotificationRead(id)
    }
}
