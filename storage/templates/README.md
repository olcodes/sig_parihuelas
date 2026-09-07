# Plantillas de Excel

Este directorio contiene las plantillas de Excel para la generación de reportes consolidados.

## Plantilla: plantilla_consolidado.xlsx

Esta plantilla debe contener:

1. **Hoja "Despachos Internos"** - Con cabeceras en fila 1:
   - A1: NRO VALE, B1: FECHA, C1: HORA, D1: TURNO, E1: ÁREA, F1: SUBÁREA, G1: DESPACHADOR, H1: RECEPCIONISTA, I1: VERIFICADOR, J1: CÓDIGO, K1: PRODUCTO, L1: UNIDAD DE MEDIDA, M1: CANTIDAD, N1: COMENTARIOS

2. **Hoja "Despachos Externos"** - Con cabeceras en fila 1:
   - A1: N° VALE, B1: FECHA, C1: HORA, D1: TURNO, E1: DESPACHADOR, F1: DESTINO, G1: RUC, H1: DIRECCIÓN, I1: CHOFER, J1: BREVETE, K1: TRANSPORTISTA, L1: RUC TRANSPORTISTA, M1: PLACA TRACTO, N1: CONSTANCIA INSC. TRACTO, O1: PLACA CARRETA, P1: CONSTANCIA INSC. CARRETA, Q1: GUÍA REMISIÓN, R1: CÓDIGO, S1: PRODUCTO, T1: UNIDAD MEDIDA, U1: CANTIDAD, V1: COMENTARIOS, W1: ESTADO

3. **Hoja "Recepciones Internas"** - Con cabeceras en fila 1:
   - A1: NRO VALE, B1: FECHA, C1: HORA, D1: TURNO, E1: ÁREA, F1: SUBÁREA, G1: DESPACHADOR, H1: MEDIO TRANSPORTE, I1: VERIFICADOR, J1: CÓDIGO, K1: PRODUCTO, L1: UNIDAD MEDIDA, M1: CANTIDAD, N1: COMENTARIOS, O1: ESTADO

4. **Hoja "Recepciones Externas"** - Con cabeceras en fila 1:
   - A1: N° VALE, B1: FECHA, C1: HORA, D1: TURNO, E1: ORIGEN, F1: EMPRESA, G1: RUC, H1: CHOFER, I1: BREVETE, J1: N° GUÍA, K1: OBSERVACIÓN, L1: CÓD. OBS, M1: CANT. OBS, N1: CÓDIGO PRODUCTO, O1: DESCRIPCIÓN, P1: CANTIDAD, Q1: UNIDAD, R1: ESTADO

5. **Hoja adicional con Tabla Dinámica** (opcional):
   - Puede contener tablas dinámicas que apunten a las hojas de datos anteriores
   - Las tablas dinámicas se actualizarán automáticamente cuando se abra el archivo

## Importante

- NO eliminar las cabeceras (fila 1) de cada hoja
- Pueden tener formato (colores, negrita, etc.) - se preservará
- Las tablas dinámicas deben usar rangos dinámicos o amplios para acomodar datos variables
- Al exportar, los datos anteriores se eliminan y se insertan los nuevos
