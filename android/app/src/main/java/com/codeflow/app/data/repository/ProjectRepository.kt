package com.codeflow.app.data.repository

import com.codeflow.app.core.network.ApiClient
import com.codeflow.app.core.network.ApiResult
import com.codeflow.app.core.network.safeApiCall
import com.codeflow.app.data.remote.CodeFlowApi
import com.codeflow.app.data.remote.dto.ProjectDto
import com.codeflow.app.data.remote.dto.decodePage
import com.codeflow.app.domain.model.Page
import com.codeflow.app.domain.model.Project

interface ProjectRepository {
    suspend fun getProjects(page: Int = 1): ApiResult<Page<Project>>
}

class DefaultProjectRepository(
    private val api: CodeFlowApi,
) : ProjectRepository {

    override suspend fun getProjects(page: Int): ApiResult<Page<Project>> = safeApiCall {
        ApiClient.json
            .decodePage(api.projects(page), ProjectDto.serializer())
            .toDomain { it.toDomain() }
    }
}
