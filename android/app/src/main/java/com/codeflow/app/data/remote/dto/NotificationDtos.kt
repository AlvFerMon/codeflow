package com.codeflow.app.data.remote.dto

import kotlinx.serialization.KSerializer
import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable
import kotlinx.serialization.SerializationException
import kotlinx.serialization.descriptors.PrimitiveKind
import kotlinx.serialization.descriptors.PrimitiveSerialDescriptor
import kotlinx.serialization.descriptors.SerialDescriptor
import kotlinx.serialization.encoding.Decoder
import kotlinx.serialization.encoding.Encoder
import kotlinx.serialization.json.JsonDecoder
import kotlinx.serialization.json.JsonPrimitive

/**
 * Elemento de GET /api/me/notifications.
 * `kind` es "change" (cambio en un proyecto) o "alert" (requiere atención).
 */
@Serializable
data class NotificationDto(
    // Las notificaciones de base de datos de Laravel usan UUID; se acepta también un número.
    @Serializable(with = FlexibleStringSerializer::class) val id: String,
    val kind: String = "change",
    val event: String? = null,
    val title: String = "",
    val message: String? = null,
    val url: String? = null,
    @SerialName("read_at") val readAt: String? = null,
    @SerialName("created_at") val createdAt: String? = null,
    val actor: UserSummaryDto? = null,
    val project: ProjectSummaryDto? = null,
)

/** GET /api/me/notifications/unread-count → `{ "count": 3 }` */
@Serializable
data class UnreadCountDto(
    val count: Int = 0,
)

/** Lee un valor JSON que puede venir como texto (`"9b1d..."`) o como número (`12`). */
object FlexibleStringSerializer : KSerializer<String> {
    override val descriptor: SerialDescriptor =
        PrimitiveSerialDescriptor("FlexibleString", PrimitiveKind.STRING)

    override fun deserialize(decoder: Decoder): String {
        val jsonDecoder = decoder as? JsonDecoder ?: return decoder.decodeString()
        val element = jsonDecoder.decodeJsonElement()
        return (element as? JsonPrimitive)?.content
            ?: throw SerializationException("Se esperaba un texto o un número, no $element")
    }

    override fun serialize(encoder: Encoder, value: String) {
        encoder.encodeString(value)
    }
}
