function verificarPassword($password, $hash) {
    // Primero intentar con password_verify
    if (password_verify($password, $hash)) {
        return true;
    }
    
    // Si falla, podrías agregar compatibilidad con contraseñas antiguas
    // pero es mejor forzar la migración a password_hash
    
    return false;
}