import SwiftUI
import UIKit
import MessageUI

struct ContentView: View {
    @State private var clients: [Client] = []
    @State private var selectedClient: Client?
    @State private var date = Date()
    @State private var review = true
    @State private var message = ""
    @State private var status = ""
    @State private var loading = false

    var body: some View {
        NavigationStack {
            Form {
                Section("Клиент") {
                    Picker("Имя клиента", selection: $selectedClient) {
                        Text("Выберите клиента").tag(nil as Client?)
                        ForEach(clients) { client in
                            Text(client.name).tag(Optional(client))
                        }
                    }

                    HStack {
                        Text("+")
                        Text(selectedClient?.telefon ?? "")
                            .foregroundStyle(.secondary)
                    }
                }

                Section("Запись") {
                    DatePicker(
                        "Дата и время",
                        selection: $date,
                        displayedComponents: [.date, .hourAndMinute]
                    )

                    HStack {
                        Text("Пароль")
                        Spacer()
                        Text(selectedClient?.password ?? "")
                            .foregroundStyle(.secondary)
                    }

                    Toggle("Запрос отзыва", isOn: $review)
                }

                Section {
                    Button {
                        Task { await generate() }
                    } label: {
                        Label("Сформировать", systemImage: "wand.and.stars")
                            .frame(maxWidth: .infinity)
                    }
                    .disabled(selectedClient == nil || loading)
                }

                if !message.isEmpty {
                    Section("Текст сообщения") {
                        Text(message)
                            .textSelection(.enabled)

                        Button {
                            UIPasteboard.general.string = message
                            status = "✓ Текст успешно скопирован."
                        } label: {
                            Label("Скопировать", systemImage: "doc.on.doc")
                                .frame(maxWidth: .infinity)
                        }

                        Button(action: openWhatsApp) {
                            Label("Отправить в WhatsApp", systemImage: "message.fill")
                                .frame(maxWidth: .infinity)
                        }

                        Button(action: openSMS) {
                            Label("Отправить в SMS", systemImage: "bubble.left.and.bubble.right")
                                .frame(maxWidth: .infinity)
                        }
                    }

                    Section {
                        Button {
                            Task { await sendModemSMS() }
                        } label: {
                            Label("Отправить в SMS через Модем", systemImage: "antenna.radiowaves.left.and.right")
                                .frame(maxWidth: .infinity)
                        }
                        .disabled(loading)
                    }
                }

                if !status.isEmpty {
                    Section {
                        Text(status)
                            .foregroundStyle(status.contains("✓") ? .green : .red)
                    }
                }
            }
            .navigationTitle("ArtForDogs")
            .task {
                await loadClients()
            }
        }
    }

    private func loadClients() async {
        do {
            clients = try await APIClient.shared.clients()
        } catch {
            status = error.localizedDescription
        }
    }

    private func generate() async {
        guard let client = selectedClient else { return }
        loading = true
        defer { loading = false }

        let formatter = ISO8601DateFormatter()
        formatter.formatOptions = [.withInternetDateTime]
        formatter.timeZone = TimeZone.current

        do {
            message = try await APIClient.shared.generate(
                name: client.name,
                phone: client.telefon,
                password: client.password,
                date: formatter.string(from: date),
                review: review
            )
            status = ""
        } catch {
            status = error.localizedDescription
        }
    }

    private func sendModemSMS() async {
        guard let client = selectedClient else { return }
        loading = true
        defer { loading = false }

        do {
            status = try await APIClient.shared.sendSMS(
                phone: client.telefon,
                message: message
            )
        } catch {
            status = error.localizedDescription
        }
    }

    private func openWhatsApp() {
        guard let client = selectedClient else { return }
        let phone = client.telefon.filter { $0.isNumber }
        let encoded = message.addingPercentEncoding(withAllowedCharacters: .urlQueryAllowed) ?? ""
        guard let url = URL(string: "whatsapp://send?phone=\(phone)&text=\(encoded)") else { return }
        UIApplication.shared.open(url)
    }

    private func openSMS() {
        guard let client = selectedClient else { return }
        let phone = client.telefon.filter { $0.isNumber }
        let encoded = message.addingPercentEncoding(withAllowedCharacters: .urlQueryAllowed) ?? ""
        guard let url = URL(string: "sms:\(phone)&body=\(encoded)") else { return }
        UIApplication.shared.open(url)
    }
}
