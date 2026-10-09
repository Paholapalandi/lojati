<?php

header("Content-Type: application/json; charset=UTF-8");

require "config.php";

$rota = $_GET["rota"] ?? (
    $_SERVER["REQUEST_METHOD"] === "POST"
        ? "clientes/create"
        : "teste"
);

function teste() {
    echo json_encode([
        "mensagem" => "API respondendo com sucesso"
    ]);
}

function listarClientes($con) {
    try {
        $stmt = $con->query(
            "SELECT ClienteID, Nome, Email, Telefone, Endereco
             FROM Clientes"
        );

        echo json_encode(
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );

    } catch (PDOException $e) {
        http_response_code(500);

        echo json_encode([
            "erro" => "Erro ao listar clientes."
        ]);
    }
}

function adicionarCliente($con) {

    // Aceita dados enviados em JSON ou por formulário.
    $dados = json_decode(
        file_get_contents("php://input"),
        true
    );

    if (!is_array($dados)) {
        $dados = $_POST;
    }

    $nome = trim($dados["Nome"] ?? $dados["nome"] ?? "");
    $email = trim($dados["Email"] ?? $dados["email"] ?? "");
    $telefone = trim($dados["Telefone"] ?? $dados["telefone"] ?? "");
    $endereco = trim($dados["Endereco"] ?? $dados["endereco"] ?? "");

    if ($nome === "" || $email === "") {
        http_response_code(400);

        echo json_encode([
            "erro" => "Nome e email são obrigatórios."
        ]);

        return;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);

        echo json_encode([
            "erro" => "Informe um email válido."
        ]);

        return;
    }

    try {
        $stmt = $con->prepare(
            "INSERT INTO Clientes
            (Nome, Email, Telefone, Endereco)
            VALUES (?, ?, ?, ?)"
        );

        $stmt->execute([
            $nome,
            $email,
            $telefone !== "" ? $telefone : null,
            $endereco !== "" ? $endereco : null
        ]);

        http_response_code(201);

        echo json_encode([
            "mensagem" => "Cliente cadastrado com sucesso!",
            "ClienteID" => $con->lastInsertId()
        ]);

    } catch (PDOException $e) {

        if ($e->getCode() === "23000") {
            http_response_code(409);

            echo json_encode([
                "erro" => "Este email já está cadastrado."
            ]);
        } else {
            http_response_code(500);

            echo json_encode([
                "erro" => "Erro ao cadastrar cliente."
            ]);
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if ($rota === "clientes/create") {
        adicionarCliente($con);
    } else {
        http_response_code(404);

        echo json_encode([
            "erro" => "Rota não encontrada."
        ]);
    }

} elseif ($_SERVER["REQUEST_METHOD"] === "GET") {

    if ($rota === "clientes") {
        listarClientes($con);
    } else {
        teste();
    }

} else {
    http_response_code(405);

    echo json_encode([
        "erro" => "Método não permitido."
    ]);
}

?>
