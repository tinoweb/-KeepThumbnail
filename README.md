# KeepThumbnail - Sistema de Gerenciamento de Thumbnails

Um sistema completo e moderno para gerenciar, armazenar e compartilhar thumbnails de forma simples e eficiente.

## 🚀 Características

- **Upload Simples**: Interface drag-and-drop para envio de thumbnails
- **Organização**: Sistema de tags e descrições para organizar seus thumbnails
- **Busca Avançada**: Busque por título, descrição ou tags
- **Compartilhamento**: Links únicos para compartilhar thumbnails
- **Download**: Download direto dos thumbnails com contador
- **Responsivo**: Interface moderna e responsiva com TailwindCSS
- **Banco SQLite**: Banco de dados leve e portátil
- **Seguro**: Validação de arquivos e proteção contra uploads maliciosos

## 📋 Requisitos

- PHP 7.4 ou superior
- Extensão SQLite3 habilitada
- Extensão GD habilitada (para manipulação de imagens)
- Servidor web (Apache, Nginx, ou PHP built-in server)

## 🛠️ Instalação

1. **Clone ou baixe o projeto**
   ```bash
   git clone [url-do-repositorio]
   cd KeepThumbnail
   ```

2. **Configurar permissões**
   ```bash
   chmod 755 uploads/
   chmod 755 data/
   ```

3. **Iniciar servidor local (para testes)**
   ```bash
   php -S localhost:8000
   ```

4. **Acessar a aplicação**
   Abra seu navegador e acesse: `http://localhost:8000`

## 📁 Estrutura do Projeto

```
KeepThumbnail/
├── assets/
│   ├── css/
│   │   └── style.css          # Estilos personalizados
│   └── js/
│       └── script.js          # JavaScript da aplicação
├── classes/
│   └── ThumbnailManager.php   # Classe principal de gerenciamento
├── config/
│   └── database.php           # Configuração do banco SQLite
├── data/
│   └── thumbnails.db          # Banco de dados (criado automaticamente)
├── uploads/
│   └── [thumbnails]           # Diretório dos thumbnails enviados
├── index.php                  # Página principal
├── download.php               # Script de download
├── share.php                  # Página de compartilhamento
└── README.md                  # Este arquivo
```

## 🎯 Como Usar

### Enviar Thumbnail
1. Clique em "Escolher arquivo" ou arraste uma imagem para a área de upload
2. Preencha o título (obrigatório)
3. Adicione uma descrição (opcional)
4. Adicione tags separadas por vírgula (opcional)
5. Clique em "Enviar Thumbnail"

### Buscar Thumbnails
- Use a barra de busca para encontrar thumbnails por título, descrição ou tags
- A busca é realizada em tempo real

### Compartilhar Thumbnail
1. Clique no botão de compartilhamento (ícone de share) em qualquer thumbnail
2. O link será copiado automaticamente para sua área de transferência
3. Compartilhe o link com outras pessoas

### Baixar Thumbnail
- Clique no botão de download (ícone de download) para baixar o thumbnail
- O contador de downloads será incrementado automaticamente

## 🔧 Configurações

### Limites de Upload
- **Tamanho máximo**: 10MB por arquivo
- **Tipos permitidos**: JPEG, PNG, GIF, WebP
- **Dimensões**: Sem limite (recomendado: até 4K)

### Banco de Dados
O sistema usa SQLite3 e cria automaticamente:
- Tabela `thumbnails` com todos os metadados
- Índices para otimizar buscas
- Tokens únicos para compartilhamento

## 🚀 Deploy em Produção

### Hospedagem Compartilhada
1. Faça upload de todos os arquivos via FTP
2. Certifique-se que as pastas `uploads/` e `data/` têm permissão de escrita
3. Acesse sua URL

### VPS/Servidor Dedicado
1. Configure seu servidor web (Apache/Nginx)
2. Aponte o DocumentRoot para a pasta do projeto
3. Configure as permissões adequadas
4. Configure SSL/HTTPS (recomendado)

### Exemplo de configuração Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Proteger arquivos sensíveis
<Files "*.db">
    Order allow,deny
    Deny from all
</Files>
```

## 🔒 Segurança

- Validação rigorosa de tipos de arquivo
- Proteção contra upload de scripts maliciosos
- Tokens únicos para compartilhamento
- Sanitização de dados de entrada
- Proteção do banco de dados via .htaccess

## 🎨 Personalização

### Modificar Estilos
Edite o arquivo `assets/css/style.css` para personalizar a aparência.

### Adicionar Funcionalidades
- Modifique `classes/ThumbnailManager.php` para adicionar novos métodos
- Atualize `index.php` para novas interfaces
- Adicione JavaScript em `assets/js/script.js`

## 📊 Estatísticas

O sistema coleta automaticamente:
- Total de thumbnails
- Total de downloads
- Espaço usado
- Data de criação/modificação

## 🐛 Solução de Problemas

### Erro de Permissão
```bash
chmod 755 uploads/ data/
chown www-data:www-data uploads/ data/
```

### Erro de Upload
- Verifique se a extensão GD está habilitada
- Confirme os limites de upload no php.ini
- Verifique permissões das pastas

### Banco de Dados
- O arquivo `data/thumbnails.db` é criado automaticamente
- Certifique-se que a pasta `data/` tem permissão de escrita

## 📝 Licença

Este projeto é open source e está disponível sob a licença MIT.

## 🤝 Contribuição

Contribuições são bem-vindas! Sinta-se à vontade para:
- Reportar bugs
- Sugerir melhorias
- Enviar pull requests
- Melhorar a documentação

## 📞 Suporte

Para suporte ou dúvidas:
- Abra uma issue no repositório
- Consulte a documentação
- Verifique os logs de erro do servidor

---

**KeepThumbnail** - Mantenha seus thumbnails organizados e acessíveis! 🎨
