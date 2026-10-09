package com.codeflow.app.data.repository

import com.codeflow.app.core.network.ApiClient
import com.codeflow.app.core.network.ApiResult
import com.codeflow.app.core.network.safeApiCall
import com.codeflow.app.data.remote.CodeFlowApi
import com.codeflow.app.data.remote.dto.ActivityDto
import com.codeflow.app.data.remote.dto.decodePage
import com.codeflow.app.domain.model.ActivityItem
import com.codeflow.app.domain.model.Page

interface ActivityRepository {
    /** @param projectId `null` = todos mis proyectos. */
    suspend fun getActivity(page: Int = 1, projectId: Long? = null): ApiResult<Page<ActivityItem>>
}

class DefaultActivityRepository(
    private val api: CodeFlowApi,
) : ActivityRepository {

    override suspend fun getActivity(page: Int, projectId: Long?): ApiResult<Page<ActivityItem>> = safeApiCall {
        ApiClient.json
            .decodePage(api.activity(page, projectId), ActivityDto.serializer())
            .toDomain { it.toDomain() }
    }
}
