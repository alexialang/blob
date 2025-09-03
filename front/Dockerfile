# Launch Angular for dev and hot reload
FROM node:20-alpine AS dev

WORKDIR /app

RUN apk add --no-cache bash curl git libc6-compat python3 make g++

COPY package.json package-lock.json ./
RUN npm ci

COPY . .

EXPOSE 4200

CMD ["npm", "run", "start"]

# Build Nginx for prod
FROM node:20-alpine AS build

WORKDIR /app

RUN apk add --no-cache bash curl git libc6-compat python3 make g++

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build

# Run Nginx for prod
FROM nginx:stable-alpine AS prod

RUN rm /etc/nginx/conf.d/default.conf
COPY nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=build /app/dist/blob-front /usr/share/nginx/html

EXPOSE 4200
EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
