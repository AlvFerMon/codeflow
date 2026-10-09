package com.codeflow.app.feature.activity

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.codeflow.app.core.network.ApiResult
import com.codeflow.app.data.repository.ActivityRepository
import com.codeflow.app.data.repository.ProjectRepository
import com.codeflow.app.domain.model.ActivityItem
import com.codeflow.app.domain.model.Project
import kotlinx.coroutines.Job
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

data class ActivityUiState(
    val isLoading: Boolean = true,
    /** Recarga con "deslizar para actualizar" (la lista sigue visible). */
    val isRefreshing: Boolean = false,
    val items: List<ActivityItem> = emptyList(),
    val error: String? = null,
    val currentPage: Int = 0,
    val canLoadMore: Boolean = false,
    val isLoadingMore: Boolean = false,
    val loadMoreError: String? = null,
    /** Proyectos para el filtro (si fallan, el filtro simplemente no aparece). */
    val projects: List<Project> = emptyList(),
    /** `null` = todos mis proyectos. */
    val selectedProjectId: Long? = null,
)

/** Feed de actividad: quién hizo qué, en qué proyecto, repositorio y rama, y cuándo. */
class ActivityViewModel(
    private val activityRepository: ActivityRepository,
    private val projectRepository: ProjectRepository,
) : ViewModel() {

    private val _uiState = MutableStateFlow(ActivityUiState())
    val uiState: StateFlow<ActivityUiState> = _uiState.asStateFlow()

    private var loadJob: Job? = null

    init {
        load()
        loadProjects()
    }

    /** Carga (o recarga) la primera página mostrando el indicador de pantalla completa. */
    fun load() = fetchFirstPage(showFullLoading = true)

    /** Recarga silenciosa: al volver a la pestaña o al deslizar hacia abajo. */
    fun refresh() = fetchFirstPage(showFullLoading = false)

    fun selectProject(projectId: Long?) {
        if (projectId == _uiState.value.selectedProjectId) return
        _uiState.update { it.copy(selectedProjectId = projectId, items = emptyList()) }
        load()
    }

    fun loadMore() {
        val state = _uiState.value
        if (state.isLoading || state.isRefreshing || state.isLoadingMore || !state.canLoadMore) return
        _uiState.update { it.copy(isLoadingMore = true, loadMoreError = null) }
        viewModelScope.launch {
            when (val result = activityRepository.getActivity(state.currentPage + 1, state.selectedProjectId)) {
                is ApiResult.Success -> _uiState.update {
                    it.copy(
                        isLoadingMore = false,
                        items = (it.items + result.data.items).distinctBy { item -> item.id },
                        currentPage = result.data.currentPage,
                        canLoadMore = result.data.hasMore,
                    )
                }
                is ApiResult.Error -> _uiState.update { it.copy(isLoadingMore = false, loadMoreError = result.message) }
            }
        }
    }

    private fun fetchFirstPage(showFullLoading: Boolean) {
        loadJob?.cancel()
        _uiState.update {
            it.copy(
                isLoading = showFullLoading || it.items.isEmpty(),
                isRefreshing = !showFullLoading && it.items.isNotEmpty(),
                error = null,
                loadMoreError = null,
            )
        }
        val projectId = _uiState.value.selectedProjectId
        loadJob = viewModelScope.launch {
            when (val result = activityRepository.getActivity(page = 1, projectId = projectId)) {
                is ApiResult.Success -> _uiState.update {
                    it.copy(
                        isLoading = false,
                        isRefreshing = false,
                        items = result.data.items,
                        currentPage = result.data.currentPage,
                        canLoadMore = result.data.hasMore,
                    )
                }
                is ApiResult.Error -> _uiState.update {
                    // Si ya había datos, se conservan y el error se muestra sin vaciar la lista
                    it.copy(isLoading = false, isRefreshing = false, error = result.message)
                }
            }
        }
    }

    private fun loadProjects() {
        viewModelScope.launch {
            val result = projectRepository.getProjects(page = 1)
            if (result is ApiResult.Success) _uiState.update { it.copy(projects = result.data.items) }
        }
    }
}
