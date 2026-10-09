package com.codeflow.app.data.remote.dto

import com.codeflow.app.core.network.ApiClient
import com.codeflow.app.data.repository.toDomain
import com.codeflow.app.domain.model.ActivityCategory
import kotlinx.serialization.json.JsonElement
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNull
import org.junit.Test

class ActivityDtoTest {

    private val json = """
        {
          "current_page": 1, "last_page": 2, "per_page": 30, "total": 31,
          "data": [{
            "id": 7, "action": "commit.created", "category": "commit",
            "summary": "subió 1 cambio a la rama main",
            "actor": {"id": 2, "name": "María"},
            "project": {"id": 1, "name": "Proyecto DAM"},
            "repository": {"id": 3, "name": "app"},
            "branch": "main", "target_branch": null, "commit_sha": "a1b2c3d", "number": null,
            "properties": {"branch": "main", "sha": "a1b2c3d4"},
            "created_at": "2026-10-09T10:00:00.000000Z"
          }]
        }
    """.trimIndent()

    @Test
    fun `decodifica el feed de actividad`() {
        val element = ApiClient.json.parseToJsonElement(json) as JsonElement
        val page = ApiClient.json.decodePage(element, ActivityDto.serializer())
        val item = page.data.single().toDomain()

        assertEquals(2, page.lastPage)
        assertEquals("María", item.actorName)
        assertEquals(ActivityCategory.COMMIT, item.category)
        assertEquals("Proyecto DAM", item.projectName)
        assertEquals("app", item.repositoryName)
        assertEquals("main", item.branch)
        assertNull(item.targetBranch)
        assertEquals("a1b2c3d", item.commitSha)
    }
}
