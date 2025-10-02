<?php
echo "<h1>Teste de Acesso ao PDV</h1>";
echo "<p>Se você consegue ver esta página, o PHP está funcionando.</p>";

echo "<h2>URLs para testar:</h2>";
echo "<ul>";
echo "<li><a href='/pdv' target='_blank'>http://erp.local/pdv</a> (URL limpa)</li>";
echo "<li><a href='/index.php/pdv' target='_blank'>http://erp.local/index.php/pdv</a> (URL com index.php)</li>";
echo "<li><a href='/pdv-direct' target='_blank'>http://erp.local/pdv-direct</a> (PDV sem filtros)</li>";
echo "<li><a href='/login-pdv' target='_blank'>http://erp.local/login-pdv</a> (Login PDV)</li>";
echo "</ul>";

echo "<h2>Informações do sistema:</h2>";
echo "Servidor: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Script Name: " . $_SERVER['SCRIPT_NAME'] . "<br>";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "<br>";
echo "HTTP Host: " . $_SERVER['HTTP_HOST'] . "<br>";

echo "<h2>Verificação de mod_rewrite:</h2>";
if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    if (in_array('mod_rewrite', $modules)) {
        echo "✅ mod_rewrite está ATIVO";
    } else {
        echo "❌ mod_rewrite NÃO está ativo";
    }
} else {
    echo "⚠️ Não foi possível verificar os módulos do Apache";
}
?>
