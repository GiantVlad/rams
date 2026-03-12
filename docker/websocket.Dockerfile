FROM node:20-alpine
WORKDIR /app

# Install dependencies
COPY package*.json ./
RUN npm install --omit=dev

# Copy application files
COPY websocket-server.js ./

EXPOSE 8080
CMD ["node", "websocket-server.js"]
