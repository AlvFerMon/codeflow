package com.codeflow.app.core.notifications

import com.codeflow.app.domain.model.AppNotification
import com.codeflow.app.domain.model.NotificationKind
import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Test

class NotificationPollerTest {

    private fun notification(id: String) = AppNotification(
        id = id, kind = NotificationKind.CHANGE, event = "commit.created", title = "Nuevos cambios",
        message = null, url = null, isRead = false, createdAt = null, actorName = null, projectName = null,
    )

    @Test
    fun `el primer sondeo no avisa de las pendientes`() {
        assertTrue(NotificationPoller.newNotifications(listOf(notification("a")), alreadyNotified = null).isEmpty())
    }

    @Test
    fun `solo se avisa de las que no se habian mostrado`() {
        val fresh = NotificationPoller.newNotifications(
            listOf(notification("a"), notification("b"), notification("c")),
            alreadyNotified = setOf("a", "c"),
        )
        assertEquals(listOf("b"), fresh.map { it.id })
    }
}
