/*
  este servicio prepara y verifica las contraseñas.
*/

class CredencialServicio {
    static ITERACIONES = 100000;
    static TAMANO_SALT = 16;
    static TAMANO_HASH = 32;

    static obtenerCrypto() {
        if (!globalThis.crypto || !globalThis.crypto.subtle || !globalThis.crypto.getRandomValues) {
            throw new Error("La Web Crypto API no está disponible.");
        }

        return globalThis.crypto;
    }

    static bytesABase64(bytes) {
        let texto = "";
        bytes.forEach(byte => {
            texto += String.fromCharCode(byte);
        });
        return btoa(texto);
    }

    static base64ABytes(valor) {
        const texto = atob(valor);
        return Uint8Array.from(texto, caracter => caracter.charCodeAt(0));
    }

    static async generarHash(contrasena, salt) {
        const cryptoApi = this.obtenerCrypto();
        const datos = new TextEncoder().encode(contrasena);
        const saltBytes = salt || cryptoApi.getRandomValues(new Uint8Array(this.TAMANO_SALT));
        const material = await cryptoApi.subtle.importKey(
            "raw",
            datos,
            "PBKDF2",
            false,
            ["deriveBits"]
        );
        const bits = await cryptoApi.subtle.deriveBits(
            {
                name: "PBKDF2",
                salt: saltBytes,
                iterations: this.ITERACIONES,
                hash: "SHA-256"
            },
            material,
            this.TAMANO_HASH * 8
        );

        return {
            hash: this.bytesABase64(new Uint8Array(bits)),
            salt: this.bytesABase64(saltBytes)
        };
    }

    static async crearCredenciales(contrasena) {
        if (typeof contrasena !== "string") {
            throw new Error("La contraseña no es válida.");
        }

        // El salt no es secreto, pero hace distinto cada hash.
        return this.generarHash(contrasena);
    }

    static async verificarContrasena(contrasena, hashGuardado, saltGuardado) {
        if (typeof contrasena !== "string" || !hashGuardado || !saltGuardado) {
            return false;
        }

        const salt = this.base64ABytes(saltGuardado);
        const credenciales = await this.generarHash(contrasena, salt);
        return credenciales.hash === hashGuardado;
    }
}

globalThis.CredencialServicio = CredencialServicio;
