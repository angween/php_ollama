# Ollama with PHP

Accessing [Ollama](https://ollama.com) REST API with PHP. Main purpose is to connect the AI with Database (MySQL/MariaDB in my case), so you can 'talk' to your data with natural language. Make sure you have installed and running Ollama on your system.


This app is really depends on the LLM model and your rigs (NPU TOPS, VRAM or RAM), I still get random result chatting with the AI even using the same question. There are LLM models for SQL Query generation like: DuckDB and SQLcoder but often they both failed making a good Queries.

FYI: I'm using PHP 8.2^ and MariaDB. Laptop RTX 3070 8 VRAM

Features:
1. Chat with Ollama's model via PHP
2. Chat with your Database using Ollama's model
3. Saved conversations

Installation:
1. Install [Ollama](https://ollama.com)
2. Install your favorites LLM's model
3. Ready your database 
4. Edit `.env` to your system configuration
5. Have fun!

For LLM's model Ollama I'm using:
1. LLama3: from Meta
2. Qwen2: from Alibaba, good for other language beside English

TODO:
* Search conversation
* Dynamically show LLMs for selection, by retrieving the LLMs model with Ollama CLI
* (Long term) add serpapi for searching external data from internet
* (Long term) as Telegram Bot
* (Long term) as WhatsApp API Bot

[Lazwardi](https://github.com/angween) | ...
