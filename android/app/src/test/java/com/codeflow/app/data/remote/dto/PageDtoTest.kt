package com.codeflow.app.data.remote.dto

import com.codeflow.app.core.network.ApiClient
import kotlinx.serialization.json.JsonElement
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNull
import org.junit.Test

class PageDtoTest {

    private val json = ApiClient.json

    private fun parse(text: String): JsonElement = json.parseToJsonElement(text)

    @Test
    fun `decodifica el paginador de Laravel de proyectos`() {
        val body = """
            {
              "current_page": 1,
              "data": [
                {"id": 7, "name": "CodeFlow", "description": null, "owner_id": 1, "settings": null,
                 "created_at": "2026-10-08T10:00:00.000000Z", "updated_at": "2026-10-08T10:00:00.000000Z",
                 "owner": {"id": 1, "name": "Ana"}}
              ],
              "first_page_url": "http://localhost/api/projects?page=1",
              "from": 1, "last_page": 2, "per_page": 20, "total": 21,
              "links": [], "next_page_url": "http://localhost/api/projects?page=2", "path": "x",
              "prev_page_url": null, "to": 20
            }
        """.trimIndent()

        val page = json.decodePage(parse(body), ProjectDto.serializer())

        assertEquals(1, page.currentPage)
        assertEquals(2, page.lastPage)
        assertEquals(1, page.data.size)
        assertEquals("Ana", page.data[0].owner?.name)
        assertNull(page.data[0].description)
    }

    @Test
    fun `decodifica un array plano como una sola pagina`() {
        val page = json.decodePage(parse("""[{"id": 1, "name": "A"}, {"id": 2, "name": "B"}]"""), ProjectDto.serializer())

        assertEquals(2, page.data.size)
        assertEquals(1, page.currentPage)
        assertEquals(1, page.lastPage)
    }

    @Test
    fun `acepta ids de notificacion como UUID o como numero`() {
        val body = """
            {"data": [
              {"id": "9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d", "kind": "alert", "event": "pull_request.conflict",
               "title": "Conflicto", "message": "m", "url": null, "read_at": null,
               "created_at": "2026-10-08T10:00:00.000000Z", "actor": {"id": 2, "name": "Luis"},
               "project": {"id": 7, "name": "CodeFlow"}},
              {"id": 15, "kind": "change", "title": "Nuevo commit", "read_at": "2026-10-08T11:00:00.000000Z"}
            ], "current_page": 1, "last_page": 1}
        """.trimIndent()

        val page = json.decodePage(parse(body), NotificationDto.serializer())

        assertEquals("9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d", page.data[0].id)
        assertEquals("15", page.data[1].id)
        assertEquals("CodeFlow", page.data[0].project?.name)
    }
}
