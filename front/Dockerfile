# Build Angular for CSR
FROM node:20-alpine AS build

WORKDIR /app

RUN apk add --no-cache bash curl git libc6-compat python3 make g++

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build

# Build Nginx for SSR
FROM nginx:stable-alpine AS runtime

RUN rm /etc/nginx/conf.d/default.conf

COPY nginx.conf /etc/nginx/conf.d/default.conf

COPY --from=build /app/dist/blob-front /usr/share/nginx/html

EXPOSE 4200
EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
