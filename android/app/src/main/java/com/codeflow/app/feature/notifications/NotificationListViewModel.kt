package com.codeflow.app.feature.notifications

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.codeflow.app.core.network.ApiResult
import com.codeflow.app.data.repository.NotificationRepository
import com.codeflow.app.domain.model.AppNotification
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

data class NotificationListUiState(
    val isLoading: Boolean = true,
    val notifications: List<AppNotification> = emptyList(),
    val error: String? = null,
    val currentPage: Int = 0,
    val canLoadMore: Boolean = false,
    val isLoadingMore: Boolean = false,
    val loadMoreError: String? = null,
    /** Mensaje puntual (snackbar), p. ej. si falla marcar como leída. */
    val message: String? = null,
)

class NotificationListViewModel(
    private val notificationRepository: NotificationRepository,
) : ViewModel() {

    private val _uiState = MutableStateFlow(NotificationListUiState())
    val uiState: StateFlow<NotificationListUiState> = _uiState.asStateFlow()

    init {
        load()
        // Si el sondeo detecta notificaciones nuevas (sube el contador), se recarga la lista.
        viewModelScope.launch {
            var previous = notificationRepository.unreadCount.value
            notificationRepository.unreadCount.collect { count ->
                if (count > previous && !_uiState.value.isLoading) load()
                previous = count
            }
        }
    }

    fun markAllAsRead() {
        val before = _uiState.value.notifications
        _uiState.update { state -> state.copy(notifications = state.notifications.map { it.copy(isRead = true) }) }
        viewModelScope.launch {
            val result = notificationRepository.markAllAsRead()
            if (result is ApiResult.Error) {
                _uiState.update { it.copy(notifications = before, message = result.message) }
            }
        }
    }

    fun load() {
        _uiState.update { it.copy(isLoading = true, error = null, loadMoreError = null) }
        viewModelScope.launch {
            when (val result = notificationRepository.getNotifications(page = 1)) {
                is ApiResult.Success -> _uiState.update {
                    it.copy(
                        isLoading = false,
                        notifications = result.data.items,
                        currentPage = result.data.currentPage,
                        canLoadMore = result.data.hasMore,
                    )
                }
                is ApiResult.Error -> _uiState.update { it.copy(isLoading = false, error = result.message) }
            }
        }
    }

    fun loadMore() {
        val state = _uiState.value
        if (state.isLoading || state.isLoadingMore || !state.canLoadMore) return

        _uiState.update { it.copy(isLoadingMore = true, loadMoreError = null) }
        viewModelScope.launch {
            when (val result = notificationRepository.getNotifications(page = state.currentPage + 1)) {
                is ApiResult.Success -> _uiState.update {
                    it.copy(
                        isLoadingMore = false,
                        notifications = (it.notifications + result.data.items).distinctBy { n -> n.id },
                        currentPage = result.data.currentPage,
                        canLoadMore = result.data.hasMore,
                    )
                }
                is ApiResult.Error -> _uiState.update {
                    it.copy(isLoadingMore = false, loadMoreError = result.message)
                }
            }
        }
    }

    /**
     * Al pulsar una notificación se marca como leída (actualización optimista: si la API falla,
     * se revierte y se avisa).
     * TODO(fases siguientes): navegar al recurso de `notification.url` (PR, issue, commit...).
     */
    fun onNotificationClick(notification: AppNotification) {
        if (notification.isRead) return

        setRead(notification.id, isRead = true)
        viewModelScope.launch {
            val result = notificationRepository.markAsRead(notification.id)
            if (result is ApiResult.Error) {
                setRead(notification.id, isRead = false)
                _uiState.update { it.copy(message = result.message) }
            }
        }
    }

    fun onMessageShown() {
        _uiState.update { it.copy(message = null) }
    }

    private fun setRead(id: String, isRead: Boolean) {
        _uiState.update { state ->
            state.copy(
                notifications = state.notifications.map { n ->
                    if (n.id == id) n.copy(isRead = isRead) else n
                },
            )
        }
    }
}
