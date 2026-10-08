package com.codeflow.app.feature.projects

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.codeflow.app.core.network.ApiResult
import com.codeflow.app.data.repository.AuthRepository
import com.codeflow.app.data.repository.NotificationRepository
import com.codeflow.app.data.repository.ProjectRepository
import com.codeflow.app.domain.model.Project
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

data class ProjectListUiState(
    val isLoading: Boolean = true,
    val projects: List<Project> = emptyList(),
    /** Error de la carga inicial (pantalla completa con "Reintentar"). */
    val error: String? = null,
    val currentPage: Int = 0,
    val canLoadMore: Boolean = false,
    val isLoadingMore: Boolean = false,
    /** Error al cargar más páginas (se muestra en el pie de la lista). */
    val loadMoreError: String? = null,
    val unreadNotifications: Int = 0,
    val isLoggingOut: Boolean = false,
)

class ProjectListViewModel(
    private val projectRepository: ProjectRepository,
    private val notificationRepository: NotificationRepository,
    private val authRepository: AuthRepository,
) : ViewModel() {

    private val _uiState = MutableStateFlow(ProjectListUiState())
    val uiState: StateFlow<ProjectListUiState> = _uiState.asStateFlow()

    init {
        load()
    }

    /** Carga (o recarga) la primera página. */
    fun load() {
        _uiState.update { it.copy(isLoading = true, error = null, loadMoreError = null) }
        viewModelScope.launch {
            when (val result = projectRepository.getProjects(page = 1)) {
                is ApiResult.Success -> _uiState.update {
                    it.copy(
                        isLoading = false,
                        projects = result.data.items,
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
            when (val result = projectRepository.getProjects(page = state.currentPage + 1)) {
                is ApiResult.Success -> _uiState.update {
                    it.copy(
                        isLoadingMore = false,
                        projects = (it.projects + result.data.items).distinctBy { p -> p.id },
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

    /** Contador del icono de notificaciones. Si falla, simplemente no se muestra la insignia. */
    fun refreshUnreadCount() {
        viewModelScope.launch {
            val result = notificationRepository.getUnreadCount()
            if (result is ApiResult.Success) {
                _uiState.update { it.copy(unreadNotifications = result.data) }
            }
        }
    }

    /** Al cerrar la sesión, la navegación (que observa la sesión) vuelve al Login. */
    fun logout() {
        if (_uiState.value.isLoggingOut) return
        _uiState.update { it.copy(isLoggingOut = true) }
        viewModelScope.launch {
            authRepository.logout()
        }
    }
}
