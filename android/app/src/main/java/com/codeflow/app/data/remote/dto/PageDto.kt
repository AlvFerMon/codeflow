package com.codeflow.app.data.remote.dto

import kotlinx.serialization.KSerializer
import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable
import kotlinx.serialization.SerializationException
import kotlinx.serialization.builtins.ListSerializer
import kotlinx.serialization.json.Json
import kotlinx.serialization.json.JsonArray
import kotlinx.serialization.json.JsonElement
import kotlinx.serialization.json.JsonObject

/**
 * Paginador de Laravel (`->paginate()`):
 * `{ "data": [...], "current_page": 1, "last_page": 3, "per_page": 20, "total": 45, ... }`
 */
@Serializable
data class PageDto<T>(
    val data: List<T> = emptyList(),
    @SerialName("current_page") val currentPage: Int = 1,
    @SerialName("last_page") val lastPage: Int = 1,
    @SerialName("per_page") val perPage: Int? = null,
    val total: Int? = null,
)

/**
 * Decodifica una respuesta de listado de forma robusta:
 *  - objeto paginado de Laravel → [PageDto] tal cual;
 *  - array JSON plano (`[...]`, endpoints sin paginar) → una única página.
 */
fun <T> Json.decodePage(element: JsonElement, itemSerializer: KSerializer<T>): PageDto<T> =
    when (element) {
        is JsonArray -> PageDto(
            data = decodeFromJsonElement(ListSerializer(itemSerializer), element),
            currentPage = 1,
            lastPage = 1,
        )
        is JsonObject -> decodeFromJsonElement(PageDto.serializer(itemSerializer), element)
        else -> throw SerializationException("Se esperaba un listado y se recibió: $element")
    }
