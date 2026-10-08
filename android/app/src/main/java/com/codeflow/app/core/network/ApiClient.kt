package com.codeflow.app.core.network

import com.codeflow.app.data.remote.CodeFlowApi
import java.util.concurrent.TimeUnit
import kotlinx.serialization.json.Json
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.kotlinx.serialization.asConverterFactory

/**
 * Construye el cliente HTTP (OkHttp) y la interfaz Retrofit de la API de CodeFlow.
 */
object ApiClient {

    /** Configuración JSON común: tolerante con campos nuevos que añada el backend. */
    val json: Json = Json {
        ignoreUnknownKeys = true
        coerceInputValues = true
    }

    fun create(
        baseUrl: String,
        tokenProvider: () -> String?,
        onUnauthorized: () -> Unit,
        enableBodyLogging: Boolean,
    ): CodeFlowApi {
        val logging = HttpLoggingInterceptor().apply {
            // El cuerpo de las peticiones solo se registra en debug (puede contener contraseñas).
            level = if (enableBodyLogging) {
                HttpLoggingInterceptor.Level.BODY
            } else {
                HttpLoggingInterceptor.Level.NONE
            }
            redactHeader("Authorization")
        }

        val client = OkHttpClient.Builder()
            .addInterceptor(AuthInterceptor(tokenProvider))
            .addInterceptor(UnauthorizedInterceptor(onUnauthorized))
            .addInterceptor(logging)
            .connectTimeout(15, TimeUnit.SECONDS)
            .readTimeout(30, TimeUnit.SECONDS)
            .writeTimeout(30, TimeUnit.SECONDS)
            .build()

        return Retrofit.Builder()
            .baseUrl(baseUrl)
            .client(client)
            .addConverterFactory(json.asConverterFactory("application/json".toMediaType()))
            .build()
            .create(CodeFlowApi::class.java)
    }
}
