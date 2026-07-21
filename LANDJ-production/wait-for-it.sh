#!/bin/bash
# wait-for-it.sh

set -e

host="$1"
shift
cmd="$@"

until nc -z "$host" 3306; do
  echo "Esperando a que la base de datos esté lista en $host:3306..."
  sleep 1
done

echo "¡Base de datos lista! Ejecutando el comando..."
exec "$cmd"
