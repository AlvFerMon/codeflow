package com.codeflow.app.feature.auth

import com.codeflow.app.core.network.ApiResult
import com.codeflow.app.core.network.MSG_NETWORK
import com.codeflow.app.data.repository.AuthRepository
import com.codeflow.app.domain.model.User
import kotlinx.coroutines.CompletableDeferred
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.test.UnconfinedTestDispatcher
import kotlinx.coroutines.test.resetMain
import kotlinx.coroutines.test.runTest
import kotlinx.coroutines.test.setMain
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNotNull
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Before
import org.junit.Test

@OptIn(ExperimentalCoroutinesApi::class)
class LoginViewModelTest {

    private class FakeAuthRepository : AuthRepository {
        val calls = mutableListOf<Triple<String, String, String>>()
        var pending: CompletableDeferred<ApiResult<User>>? = null
        var result: ApiResult<User> = ApiResult.Success(User(1, "Ana", "ana@codeflow.test"))

        override suspend fun login(email: String, password: String, deviceName: String): ApiResult<User> {
            calls += Triple(email, password, deviceName)
            return pending?.await() ?: result
        }

        override suspend fun logout() = Unit
    }

    private lateinit var repository: FakeAuthRepository
    private lateinit var viewModel: LoginViewModel

    @Before
    fun setUp() {
        Dispatchers.setMain(UnconfinedTestDispatcher())
        repository = FakeAuthRepository()
        viewModel = LoginViewModel(repository, deviceName = "Android Test")
    }

    @After
    fun tearDown() {
        Dispatchers.resetMain()
    }

    @Test
    fun `campos vacios muestran errores y no llaman a la API`() {
        viewModel.submit()

        val state = viewModel.uiState.value
        assertEquals("Introduce tu correo electrónico.", state.emailError)
        assertEquals("Introduce tu contraseña.", state.passwordError)
        assertFalse(state.isLoading)
        assertTrue(repository.calls.isEmpty())
    }

    @Test
    fun `email con formato incorrecto muestra error`() {
        viewModel.onEmailChange("ana-arroba-codeflow")
        viewModel.onPasswordChange("password")

        viewModel.submit()

        assertEquals("El correo electrónico no es válido.", viewModel.uiState.value.emailError)
        assertNull(viewModel.uiState.value.passwordError)
        assertTrue(repository.calls.isEmpty())
    }

    @Test
    fun `editar un campo borra su error`() {
        viewModel.submit()
        viewModel.onEmailChange("a")

        assertNull(viewModel.uiState.value.emailError)
        assertNotNull(viewModel.uiState.value.passwordError)
    }

    @Test
    fun `datos validos llaman al repositorio con el email recortado y el dispositivo`() = runTest {
        viewModel.onEmailChange("  ana@codeflow.test ")
        viewModel.onPasswordChange("secreto123")

        viewModel.submit()

        assertEquals(listOf(Triple("ana@codeflow.test", "secreto123", "Android Test")), repository.calls)
        val state = viewModel.uiState.value
        assertFalse(state.isLoading)
        assertNull(state.emailError)
        assertNull(state.generalError)
        assertEquals("", state.password)
    }

    @Test
    fun `muestra el estado de carga mientras espera a la API`() = runTest {
        val pending = CompletableDeferred<ApiResult<User>>()
        repository.pending = pending
        viewModel.onEmailChange("ana@codeflow.test")
        viewModel.onPasswordChange("secreto123")

        viewModel.submit()
        assertTrue(viewModel.uiState.value.isLoading)

        viewModel.submit() // un segundo toque no lanza otra petición
        assertEquals(1, repository.calls.size)

        pending.complete(ApiResult.Success(User(1, "Ana", "ana@codeflow.test")))
        assertFalse(viewModel.uiState.value.isLoading)
    }

    @Test
    fun `el error 422 del servidor se muestra en el campo email`() = runTest {
        repository.result = ApiResult.Error(
            code = 422,
            message = "Credenciales incorrectas.",
            fieldErrors = mapOf("email" to listOf("Credenciales incorrectas.")),
        )
        viewModel.onEmailChange("ana@codeflow.test")
        viewModel.onPasswordChange("mala")

        viewModel.submit()

        val state = viewModel.uiState.value
        assertEquals("Credenciales incorrectas.", state.emailError)
        assertNull(state.generalError)
        assertFalse(state.isLoading)
    }

    @Test
    fun `un error sin campos se muestra como error general`() = runTest {
        repository.result = ApiResult.Error(code = null, message = MSG_NETWORK)
        viewModel.onEmailChange("ana@codeflow.test")
        viewModel.onPasswordChange("secreto123")

        viewModel.submit()

        assertEquals(MSG_NETWORK, viewModel.uiState.value.generalError)
        assertNull(viewModel.uiState.value.emailError)
    }
}
