import SwiftUI
import UIKit

struct ContentView: View {
    @State private var clients: [Client] = []
    @State private var selectedClient: Client?
    @State private var date = Date()
    @State private var review = true
    @State private var message = ""
    @State private var status = ""
    @State private var loading = false
    @State private var showClientPicker = false
    @State private var showDatePicker = false

    private let blue = Color(red: 0.04, green: 0.52, blue: 1.0)

    var body: some View {
        ZStack {
            WebLikeBackground()

            ScrollView {
                VStack(spacing: 12) {
                    header

                    if !status.isEmpty {
                        statusView
                    }

                    clientCard
                    bookingCard
                    generateButton

                    if !message.isEmpty {
                        messageCard
                        actionButtons
                    }
                }
                .padding(.horizontal, 14)
                .padding(.top, 14)
                .padding(.bottom, 34)
            }
            .scrollIndicators(.hidden)
        }
        .preferredColorScheme(.light)
        .task {
            await loadClients()
        }
        .sheet(isPresented: $showClientPicker) {
            ClientPickerSheet(
                clients: clients,
                selectedClient: $selectedClient
            )
            .presentationDetents([.medium, .large])
            .presentationDragIndicator(.visible)
        }
        .sheet(isPresented: $showDatePicker) {
            DatePickerSheet(date: $date)
                .presentationDetents([.height(360)])
                .presentationDragIndicator(.visible)
        }
    }

    private var header: some View {
        HStack(spacing: 10) {
            Image(systemName: "message.fill")
                .font(.system(size: 22, weight: .semibold))
            Text("SMS Manager")
                .font(.system(size: 23, weight: .bold))
        }
        .foregroundStyle(blue)
        .frame(maxWidth: .infinity)
        .frame(height: 70)
        .background(.white.opacity(0.82))
        .clipShape(RoundedRectangle(cornerRadius: 20, style: .continuous))
        .overlay(
            RoundedRectangle(cornerRadius: 20, style: .continuous)
                .stroke(.white.opacity(0.95), lineWidth: 1)
        )
        .shadow(color: .black.opacity(0.08), radius: 20, y: 8)
    }

    private var clientCard: some View {
        WebCard(title: "Клиент") {
            Button {
                showClientPicker = true
            } label: {
                HStack {
                    Text("Имя клиента")
                        .foregroundStyle(.primary)

                    Spacer()

                    Text(selectedClient?.name ?? "Выберите клиента")
                        .foregroundStyle(selectedClient == nil ? .secondary : .primary)

                    Image(systemName: "chevron.up.chevron.down")
                        .font(.caption.weight(.semibold))
                        .foregroundStyle(.secondary)
                }
            }
            .buttonStyle(.plain)

            Divider()

            HStack(spacing: 8) {
                Text("+")
                    .font(.system(size: 21, weight: .medium))

                Text(selectedClient?.telefon ?? "")
                    .foregroundStyle(.secondary)

                Spacer()
            }
        }
    }

    private var bookingCard: some View {
        WebCard(title: "Запись") {
            Button {
                showDatePicker = true
            } label: {
                HStack {
                    Text("Дата и время")
                        .foregroundStyle(.primary)

                    Spacer()

                    Text(date.formatted(.dateTime.day().month(.abbreviated).year()))
                        .foregroundStyle(.primary)
                        .padding(.horizontal, 14)
                        .padding(.vertical, 9)
                        .background(Color.gray.opacity(0.12))
                        .clipShape(Capsule())

                    Text(date.formatted(.dateTime.hour().minute()))
                        .foregroundStyle(.primary)
                        .padding(.horizontal, 14)
                        .padding(.vertical, 9)
                        .background(Color.gray.opacity(0.12))
                        .clipShape(Capsule())
                }
            }
            .buttonStyle(.plain)

            Divider()

            HStack {
                Text("Пароль")
                Spacer()
                Text(selectedClient?.password ?? "")
                    .foregroundStyle(.secondary)
            }

            Divider()

            Toggle("Запрос отзыва", isOn: $review)
                .tint(.green)
        }
    }

    private var generateButton: some View {
        Button {
            Task { await generate() }
        } label: {
            HStack(spacing: 10) {
                if loading {
                    ProgressView()
                        .tint(blue)
                } else {
                    Image(systemName: "wand.and.stars")
                        .font(.system(size: 20, weight: .medium))
                }

                Text(loading ? "Формирование..." : "Сформировать")
                    .font(.system(size: 17, weight: .semibold))
            }
            .foregroundStyle(selectedClient == nil ? .secondary : blue)
            .frame(maxWidth: .infinity)
            .frame(height: 58)
            .background(.white.opacity(0.88))
            .clipShape(Capsule())
            .shadow(color: .black.opacity(0.08), radius: 16, y: 6)
        }
        .buttonStyle(.plain)
        .disabled(selectedClient == nil || loading)
        .padding(.top, 4)
    }

    private var messageCard: some View {
        VStack(alignment: .leading, spacing: 12) {
            Text("Текст сообщения")
                .font(.system(size: 17, weight: .semibold))
                .foregroundStyle(.secondary)

            Text(message)
                .font(.system(size: 17))
                .foregroundStyle(.primary)
                .frame(maxWidth: .infinity, alignment: .leading)
                .textSelection(.enabled)
        }
        .padding(18)
        .background(
            LinearGradient(
                colors: [Color(red: 1, green: 0.84, blue: 0.22),
                         Color(red: 1, green: 0.76, blue: 0.16)],
                startPoint: .topLeading,
                endPoint: .bottomTrailing
            )
        )
        .clipShape(RoundedRectangle(cornerRadius: 20, style: .continuous))
        .shadow(color: .black.opacity(0.10), radius: 18, y: 8)
        .padding(.top, 8)
    }

    private var actionButtons: some View {
        VStack(spacing: 8) {
            HStack(spacing: 8) {
                ActionButton(title: "Скопировать", systemImage: "doc.on.doc") {
                    UIPasteboard.general.string = message
                    status = "✓ Текст успешно скопирован."
                }

                ActionButton(title: "WhatsApp", systemImage: "message.fill") {
                    openWhatsApp()
                }
            }

            HStack(spacing: 8) {
                ActionButton(title: "SMS", systemImage: "bubble.left.and.bubble.right") {
                    openSMS()
                }

                ActionButton(title: "SMS через модем", systemImage: "antenna.radiowaves.left.and.right", filled: true) {
                    Task { await sendModemSMS() }
                }
            }
        }
    }

    private var statusView: some View {
        Text(status)
            .font(.system(size: 16, weight: .semibold))
            .foregroundStyle(status.contains("✓") ? Color.green : Color.red)
            .frame(maxWidth: .infinity, alignment: .leading)
            .padding(.horizontal, 6)
    }

    private func loadClients() async {
        do {
            clients = try await APIClient.shared.clients()
            if selectedClient == nil {
                selectedClient = clients.first
            }
        } catch {
            status = error.localizedDescription
        }
    }

    private func generate() async {
        guard let client = selectedClient else { return }

        loading = true
        status = ""
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
        let phone = client.telefon.filter(\.isNumber)
        let encoded = message.addingPercentEncoding(withAllowedCharacters: .urlQueryAllowed) ?? ""
        guard let url = URL(string: "whatsapp://send?phone=\(phone)&text=\(encoded)") else { return }
        UIApplication.shared.open(url)
    }

    private func openSMS() {
        guard let client = selectedClient else { return }
        let phone = client.telefon.filter(\.isNumber)
        let encoded = message.addingPercentEncoding(withAllowedCharacters: .urlQueryAllowed) ?? ""
        guard let url = URL(string: "sms:\(phone)&body=\(encoded)") else { return }
        UIApplication.shared.open(url)
    }
}

private struct WebLikeBackground: View {
    var body: some View {
        ZStack {
            LinearGradient(
                colors: [
                    Color(red: 0.93, green: 0.97, blue: 1.0),
                    Color(red: 0.97, green: 0.96, blue: 1.0),
                    Color(red: 0.93, green: 0.94, blue: 1.0)
                ],
                startPoint: .topLeading,
                endPoint: .bottomTrailing
            )

            Circle()
                .fill(Color.blue.opacity(0.16))
                .frame(width: 280)
                .blur(radius: 35)
                .offset(x: -150, y: -360)

            Circle()
                .fill(Color.purple.opacity(0.13))
                .frame(width: 260)
                .blur(radius: 40)
                .offset(x: 160, y: -300)
        }
        .ignoresSafeArea()
    }
}

private struct WebCard<Content: View>: View {
    let title: String
    @ViewBuilder let content: Content

    init(title: String, @ViewBuilder content: () -> Content) {
        self.title = title
        self.content = content()
    }

    var body: some View {
        VStack(alignment: .leading, spacing: 0) {
            Text(title)
                .font(.system(size: 17, weight: .semibold))
                .foregroundStyle(.secondary)
                .padding(.horizontal, 6)
                .padding(.bottom, 9)

            VStack(spacing: 0) {
                content
            }
            .padding(.horizontal, 18)
            .padding(.vertical, 16)
            .background(.white.opacity(0.76))
            .clipShape(RoundedRectangle(cornerRadius: 24, style: .continuous))
            .overlay(
                RoundedRectangle(cornerRadius: 24, style: .continuous)
                    .stroke(.white.opacity(0.95), lineWidth: 1)
            )
            .shadow(color: .black.opacity(0.07), radius: 20, y: 8)
        }
    }
}

private struct ActionButton: View {
    let title: String
    let systemImage: String
    var filled = false
    let action: () -> Void

    var body: some View {
        Button(action: action) {
            Label(title, systemImage: systemImage)
                .font(.system(size: 15.5, weight: .semibold))
                .foregroundStyle(filled ? .white : .primary)
                .frame(maxWidth: .infinity)
                .frame(minHeight: 48)
                .background(
                    filled
                    ? AnyShapeStyle(LinearGradient(
                        colors: [Color(red: 0.10, green: 0.55, blue: 1.0), Color(red: 0.0, green: 0.48, blue: 0.95)],
                        startPoint: .top,
                        endPoint: .bottom
                    ))
                    : AnyShapeStyle(Color.white.opacity(0.92))
                )
                .clipShape(RoundedRectangle(cornerRadius: 15, style: .continuous))
                .overlay(
                    RoundedRectangle(cornerRadius: 15, style: .continuous)
                        .stroke(Color.black.opacity(filled ? 0 : 0.07), lineWidth: 1)
                )
                .shadow(color: .black.opacity(0.07), radius: 10, y: 5)
        }
        .buttonStyle(.plain)
    }
}

private struct ClientPickerSheet: View {
    let clients: [Client]
    @Binding var selectedClient: Client?
    @Environment(\.dismiss) private var dismiss
    @State private var temporary: Client?

    var body: some View {
        NavigationStack {
            List(clients) { client in
                Button {
                    temporary = client
                } label: {
                    HStack {
                        VStack(alignment: .leading, spacing: 3) {
                            Text(client.name)
                                .foregroundStyle(.primary)
                            Text("+\(client.telefon)")
                                .font(.subheadline)
                                .foregroundStyle(.secondary)
                        }

                        Spacer()

                        if (temporary ?? selectedClient) == client {
                            Image(systemName: "checkmark")
                                .foregroundStyle(.blue)
                                .fontWeight(.bold)
                        }
                    }
                }
            }
            .navigationTitle("Выберите клиента")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Отмена") { dismiss() }
                }

                ToolbarItem(placement: .confirmationAction) {
                    Button("Готово") {
                        if let temporary {
                            selectedClient = temporary
                        }
                        dismiss()
                    }
                }
            }
            .onAppear {
                temporary = selectedClient
            }
        }
    }
}

private struct DatePickerSheet: View {
    @Binding var date: Date
    @Environment(\.dismiss) private var dismiss

    var body: some View {
        NavigationStack {
            DatePicker(
                "Дата и время",
                selection: $date,
                displayedComponents: [.date, .hourAndMinute]
            )
            .datePickerStyle(.wheel)
            .labelsHidden()
            .padding(.horizontal, 8)
            .navigationTitle("Дата и время")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .confirmationAction) {
                    Button("Готово") { dismiss() }
                }
            }
        }
    }
}
