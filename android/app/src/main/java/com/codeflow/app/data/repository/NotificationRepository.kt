package com.codeflow.app.data.repository

import com.codeflow.app.core.network.ApiClient
import com.codeflow.app.core.network.ApiResult
import com.codeflow.app.core.network.safeApiCall
import com.codeflow.app.data.remote.CodeFlowApi
import com.codeflow.app.data.remote.dto.NotificationDto
import com.codeflow.app.data.remote.dto.decodePage
import com.codeflow.app.domain.model.AppNotification
import com.codeflow.app.domain.model.Page
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update

interface NotificationRepository {
    /** Contador de no leídas compartido por toda la app (insignia de la barra inferior). */
    val unreadCount: StateFlow<Int>

    suspend fun getNotifications(page: Int = 1): ApiResult<Page<AppNotification>>

    /** Primera página de las no leídas (para avisar en la barra del sistema). */
    suspend fun getUnread(): ApiResult<List<AppNotification>>

    suspend fun refreshUnreadCount(): ApiResult<Int>

    suspend fun markAsRead(id: String): ApiResult<Unit>

    suspend fun markAllAsRead(): ApiResult<Unit>

    /** Al cerrar sesión. */
    fun reset()
}

class DefaultNotificationRepository(
    private val api: CodeFlowApi,
) : NotificationRepository {

    private val _unreadCount = MutableStateFlow(0)
    override val unreadCount: StateFlow<Int> = _unreadCount.asStateFlow()

    override suspend fun getNotifications(page: Int): ApiResult<Page<AppNotification>> = safeApiCall {
        ApiClient.json
            .decodePage(api.notifications(page, unread = null), NotificationDto.serializer())
            .toDomain { it.toDomain() }
    }

    override suspend fun getUnread(): ApiResult<List<AppNotification>> = safeApiCall {
        ApiClient.json
            .decodePage(api.notifications(page = 1, unread = 1), NotificationDto.serializer())
            .data
            .map { it.toDomain() }
    }

    override suspend fun refreshUnreadCount(): ApiResult<Int> {
        val result = safeApiCall { api.unreadNotificationCount().count }
        if (result is ApiResult.Success) _unreadCount.value = result.data
        return result
    }

    override suspend fun markAsRead(id: String): ApiResult<Unit> {
        val result = safeApiCall { api.markNotificationRead(id) }
        if (result is ApiResult.Success) _unreadCount.update { (it - 1).coerceAtLeast(0) }
        return result
    }

    override suspend fun markAllAsRead(): ApiResult<Unit> {
        val result = safeApiCall { api.markAllNotificationsRead() }
        if (result is ApiResult.Success) _unreadCount.value = 0
        return result
    }

    override fun reset() {
        _unreadCount.value = 0
    }
}
