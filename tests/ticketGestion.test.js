const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');

const context = {
  console,
  localStorage: {
    store: {},
    getItem(key) {
      return Object.prototype.hasOwnProperty.call(this.store, key)
        ? this.store[key]
        : null;
    },
    setItem(key, value) {
      this.store[key] = String(value);
    },
    removeItem(key) {
      delete this.store[key];
    },
    clear() {
      this.store = {};
    }
  },
  Array,
  Number,
  Math,
  JSON,
  Date,
  Object,
  Boolean,
  String,
  globalThis: null
};
context.globalThis = context;
context.global = context;

const cargarScript = (ruta) => {
  const codigo = fs.readFileSync(path.join(__dirname, '..', ruta), 'utf8');
  vm.runInNewContext(codigo, context, { filename: ruta });
};

cargarScript('public/assets/js/modelos/ticket.js');
cargarScript('public/assets/js/storage/ticketStorage.js');
cargarScript('public/assets/js/servicios/ticketServicio.js');
cargarScript('public/assets/js/modelos/solicitudPrograma.js');
cargarScript('public/assets/js/storage/solicitudProgramaStorage.js');
cargarScript('public/assets/js/servicios/solicitudProgramaServicio.js');

globalThis.Ticket = context.Ticket;
globalThis.TicketStorage = context.TicketStorage;
globalThis.TicketServicio = context.TicketServicio;
globalThis.SolicitudPrograma = context.SolicitudPrograma;
globalThis.SolicitudProgramaStorage = context.SolicitudProgramaStorage;
globalThis.SolicitudProgramaServicio = context.SolicitudProgramaServicio;

test('TicketServicio calcula prioridad según la incidencia real del ticket', () => {
  assert.equal(
    TicketServicio.obtenerPrioridad({
      prioridad: 2,
      equipos: [{ incidencia: 'No prende' }]
    }),
    0
  );

  assert.equal(
    TicketServicio.obtenerPrioridad({
      prioridad: 2,
      equipos: [{ incidencia: 'Teclado roto' }]
    }),
    1
  );

  assert.equal(
    TicketServicio.obtenerPrioridad({
      prioridad: 2,
      equipos: [{ incidencia: 'Mouse roto' }]
    }),
    2
  );
});

test('TicketStorage actualiza y elimina tickets por id sin tocar la prioridad', () => {
  context.localStorage.clear();

  const ticket1 = new Ticket(
    1,
    'Laboratorio',
    '2',
    '2026-08-17',
    '08:00',
    '10:00',
    'Programación',
    'Docente',
    '1A',
    'M',
    [{ numero: 1, estudiante: 'Ana', incidencia: 'No prende' }],
    2,
    'Pendiente'
  );

  const ticket2 = new Ticket(
    2,
    'Laboratorio',
    '3',
    '2026-08-17',
    '08:00',
    '10:00',
    'Programación',
    'Docente',
    '2A',
    'M',
    [{ numero: 2, estudiante: 'Luis', incidencia: 'Mouse roto' }],
    2,
    'Pendiente'
  );

  TicketStorage.guardarTickets([ticket1, ticket2]);

  TicketStorage.actualizarTicket(2, { estado: 'Realizado' });
  let tickets = TicketStorage.obtenerTickets();
  let ticketActualizado = tickets.find(t => t.id === 2);

  assert.equal(ticketActualizado.estado, 'Realizado');
  assert.equal(ticketActualizado.prioridad, 2);

  TicketStorage.eliminarTicket(1);
  tickets = TicketStorage.obtenerTickets();

  assert.equal(tickets.length, 1);
  assert.equal(tickets[0].id, 2);
});

test('SolicitudProgramaStorage guarda y cambia estados de la solicitud sin afectar tickets', () => {
  context.localStorage.clear();

  const solicitud = new SolicitudPrograma(
    101,
    'Ana Pérez',
    'Laboratorio 2',
    'Python básico',
    'Necesito instalar una versión de Python y ejercicios para la clase.',
    '2026-08-20',
    '10:00',
    'Pendiente'
  );

  SolicitudProgramaStorage.agregarSolicitud(solicitud);

  const guardada = SolicitudProgramaStorage.obtenerSolicitudes();
  assert.equal(guardada.length, 1);
  assert.equal(guardada[0].estado, 'Pendiente');

  const actualizada = SolicitudProgramaStorage.actualizarSolicitud(101, { estado: 'En preparación' });
  assert.equal(actualizada.estado, 'En preparación');

  const listaFinal = SolicitudProgramaStorage.obtenerSolicitudes();
  assert.equal(listaFinal[0].estado, 'En preparación');
  assert.equal(TicketStorage.obtenerTickets().length, 0);
});

test('SolicitudProgramaServicio normaliza bien los estados de la solicitud', () => {
  assert.equal(SolicitudProgramaServicio.normalizarEstado('Pendiente'), 'Pendiente');
  assert.equal(SolicitudProgramaServicio.normalizarEstado('En preparación'), 'En preparación');
  assert.equal(SolicitudProgramaServicio.normalizarEstado('Realizado'), 'Realizado');
  assert.equal(SolicitudProgramaServicio.normalizarEstado('Estado raro'), 'Pendiente');
});
