# Reglas de R8/ProGuard para la build release.
# Ahora mismo isMinifyEnabled = false. Retrofit, OkHttp y kotlinx.serialization
# incluyen sus propias reglas (consumer rules); si se activa la minificación,
# revisar que los DTO anotados con @Serializable se conservan.
