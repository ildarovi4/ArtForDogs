import Foundation

struct Client: Codable, Identifiable, Hashable {
    let name: String
    let telefon: String
    let password: String
    var id: String { name + "|" + telefon }
}

struct APIResponse<T: Codable>: Codable {
    let success: Bool
    let message: String?
    let clients: T?
    let phone: String?
}

final class APIClient {
    static let shared = APIClient()
    // Точный адрес сервера пользователя.
    private let baseURL = URL(string: "http://artfordogs.servehalflife.com:7777/api/ArtForDogs/server/api/")!

    func clients() async throws -> [Client] {
        let url = baseURL.appendingPathComponent("clients.php")
        let (data, response) = try await URLSession.shared.data(from: url)
        try Self.check(response)
        let result = try JSONDecoder().decode(APIResponse<[Client]>.self, from: data)
        guard result.success, let clients = result.clients else {
            throw APIError.server(result.message ?? "Не удалось получить клиентов.")
        }
        return clients
    }

    func generate(name: String, phone: String, password: String, date: String, review: Bool) async throws -> String {
        let url = baseURL.appendingPathComponent("generate.php")
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.httpBody = try JSONSerialization.data(withJSONObject: [
            "name": name, "telefon": phone, "password": password,
            "rezervDate": date, "option": review
        ])
        let (data, response) = try await URLSession.shared.data(for: request)
        try Self.check(response)
        let result = try JSONDecoder().decode(APIResponse<EmptyValue>.self, from: data)
        guard result.success, let message = result.message else {
            throw APIError.server(result.message ?? "Не удалось сформировать SMS.")
        }
        return message
    }

    func sendSMS(phone: String, message: String) async throws -> String {
        let url = baseURL.appendingPathComponent("send-sms.php")
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.httpBody = try JSONSerialization.data(withJSONObject: [
            "phone": phone, "message": message
        ])
        let (data, response) = try await URLSession.shared.data(for: request)
        try Self.check(response)
        let result = try JSONDecoder().decode(APIResponse<EmptyValue>.self, from: data)
        guard result.success else {
            throw APIError.server(result.message ?? "SMS не отправлено.")
        }
        return result.message ?? "✓ SMS успешно отправлено."
    }

    private static func check(_ response: URLResponse) throws {
        if let http = response as? HTTPURLResponse, !(200...299).contains(http.statusCode) {
            throw APIError.http(http.statusCode)
        }
    }
}

struct EmptyValue: Codable {}

enum APIError: LocalizedError {
    case http(Int)
    case server(String)
    var errorDescription: String? {
        switch self {
        case .http(let code): return "Ошибка сервера: HTTP \(code)"
        case .server(let text): return text
        }
    }
}
