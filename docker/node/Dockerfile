FROM node:20-alpine

# Install git (needed for some npm packages)
RUN apk add --no-cache git

# Upgrade npm to latest version
RUN npm install -g npm@latest

# Set working directory
WORKDIR /app

# Dependencies will be installed at runtime via volume mount
# This allows the project files to be mounted and dependencies
# to be installed in the named volume for node_modules

# Default command (can be overridden)
CMD ["npm", "test"]

